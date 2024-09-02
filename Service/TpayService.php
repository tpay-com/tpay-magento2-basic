<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Exception;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Operations\RegisterCaptureNotificationOperation;
use Magento\Sales\Model\Order\Payment\State\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Tpay\Magento2\Api\Sales\OrderRepositoryInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use tpayLibs\src\Dictionaries\ISO_codes\CurrencyCodesDictionary;

class TpayService extends RegisterCaptureNotificationOperation
{
    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderPaymentRepositoryInterface */
    protected $orderPaymentRepository;

    /** @var InvoiceRepositoryInterface */
    protected $invoiceRepository;

    /** @var BuilderInterface */
    protected $builder;

    /** @var InvoiceService */
    protected $invoiceService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        BuilderInterface $builder,
        CommandInterface $stateCommand,
        BuilderInterface $transactionBuilder,
        ManagerInterface $transactionManager,
        EventManagerInterface $eventManager,
        InvoiceService $invoiceService
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->builder = $builder;
        $this->invoiceService = $invoiceService;
        parent::__construct(
            $stateCommand,
            $transactionBuilder,
            $transactionManager,
            $eventManager
        );
    }

    public function setOrderStatePendingPayment(string $orderId, bool $sendEmail): OrderInterface
    {
        $order = $this->orderRepository->getByIncrementId($orderId);
        $order
            ->setTotalDue($order->getBaseGrandTotal())
            ->setTotalPaid(0.00)
            ->setBaseTotalPaid(0.00)
            ->setBaseTotalDue($order->getBaseGrandTotal())
            ->setState(Order::STATE_PENDING_PAYMENT)
            ->addStatusToHistory(true);

        $order->setSendEmail($sendEmail);
        $this->orderRepository->save($order);

        return $order;
    }

    public function addCommentToHistory($orderId, $comment)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);
        $order->addStatusToHistory($order->getState(), $comment);
        $this->orderRepository->save($order);
    }

    public function getPayment(string $orderId): OrderPaymentInterface
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        return $order->getPayment();
    }

    public function getOrderById(string $orderId): OrderInterface
    {
        return $this->orderRepository->getByIncrementId($orderId);
    }

    public function saveOrderPayment(OrderPaymentInterface $payment): OrderPaymentInterface
    {
        return $this->orderPaymentRepository->save($payment);
    }

    /** @return bool|string */
    protected function getTransactionDesc(array $validParams)
    {
        if (false === $validParams) {
            return false;
        }

        $error = $validParams['tr_error'];
        $paid = $validParams['tr_paid'];
        $status = $validParams['tr_status'];
        $transactionDesc = '<b>' . $validParams['tr_id'] . '</b> ';
        $transactionDesc .= 'none' === $error ? ' ' : ' Error:  <b>' . strtoupper($error) . '</b> (' . $paid . ')';

        if ('CHARGEBACK' === $status) {
            $transactionDesc .= __('Transaction has been refunded');
        }

        if (array_key_exists('test_mode', $validParams)) {
            $transactionDesc .= '<b> TEST </b>';
        }

        return $transactionDesc;
    }

    protected function getCardTransactionDesc($validParams)
    {
        if (empty($validParams)) {
            return false;
        }

        if (isset($validParams['err_desc'])) {
            return 'Payment error: ' . $validParams['err_desc'] . ', error code: ' . $validParams['err_code'];
        }

        $error = null;

        if ('declined' === $validParams['status']) {
            $error = $validParams['reason'];
        }

        $transactionDesc = (is_null($error)) ? ' ' : ' Reason:  <b>' . $error . '</b>';
        $transactionDesc .= (isset($validParams['test_mode'])) && 1 === (int)$validParams['test_mode'] ? '<b> TEST </b>' : ' ';

        return $transactionDesc;
    }

    /**
     * @return bool|OrderInterface
     * @throws Exception
     */
    public function setOrderStatus(string $orderId, array $validParams, TpayConfigInterface $tpayConfig)
    {
        $order = $this->getOrderById($orderId);
        $emailNotify = false;

        if (!$order->getId()) {
            return false;
        }

        $status = $this->determineOrderStatus($order, $validParams, $emailNotify);

        if (!$status) {
            return $order;
        }

        $order = $this->updateOrderStatus($order, $status, $emailNotify);
        $this->sendInvoiceEmailsIfNeeded($order, $tpayConfig);

        return $order;
    }

    public function setCardOrderStatus($orderId, array $validParams, TpayConfigInterface $tpayConfig)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        if (!$order->getId()) {
            return false;
        }

        $emailNotify = $this->commentCardOrder($order, $validParams, $orderId);

        if ($emailNotify) {
            $order->setSendEmail(true);
        }

        $this->sendInvoiceEmailsIfNeeded($order, $tpayConfig);
        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param float|string $amount
     * @param bool|int $skipFraudDetection
     */
    private function registerCaptureNotificationTpay(OrderPaymentInterface $payment, $amount, array $validParams, bool $skipFraudDetection = false)
    {
        $this->setTransactionIdForPayment($payment);

        $order = $payment->getOrder();
        $amount = (float)$amount;
        $invoice = $this->getInvoiceForTransactionId($order, $payment->getTransactionId());

        if (!$invoice && $payment->isCaptureFinal($amount)) {
            $invoice = $this->createAndRegisterInvoice($order, $payment);
        } else {
            $this->handlePotentialFraud($payment, $amount, $skipFraudDetection);
        }

        $this->finalizePayment($payment, $invoice, $amount, $order);
        $this->createTransactionAndAddComments($payment, $invoice, $validParams, $amount, $order);
    }

    private function setTransactionIdForPayment(OrderPaymentInterface $payment): void
    {
        $payment->setTransactionId(
            $this->transactionManager->generateTransactionId(
                $payment,
                Transaction::TYPE_CAPTURE,
                $payment->getAuthorizationTransaction()
            )
        );

        $this->orderPaymentRepository->save($payment);
    }

    private function createAndRegisterInvoice(OrderInterface $order, OrderPaymentInterface $payment, ?string $state = null): InvoiceInterface
    {
        $invoice = $order->prepareInvoice()->register();
        $invoice->setOrder($order);
        $order->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $payment->setShouldCloseParentTransaction(true);

        if ($state) {
            $order->setState($state);
        }

        $this->invoiceRepository->save($invoice);
        $this->orderRepository->save($order);
        $this->orderPaymentRepository->save($payment);

        return $invoice;
    }

    private function handlePotentialFraud(OrderPaymentInterface $payment, float $amount, bool $skipFraudDetection): void
    {
        $payment->setIsFraudDetected(!$skipFraudDetection);
        $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);

        $this->orderPaymentRepository->save($payment);
    }

    private function finalizePayment(OrderPaymentInterface $payment, ?InvoiceInterface $invoice, float $amount, OrderInterface $order): void
    {
        if (!$payment->getIsTransactionPending() && $invoice && Invoice::STATE_OPEN === $invoice->getState()) {
            $invoice->setOrder($order);
            $invoice->pay();
            $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
            $order->addRelatedObject($invoice);

            $this->invoiceRepository->save($invoice);
            $this->orderRepository->save($order);
            $this->orderPaymentRepository->save($payment);
        }
    }

    private function createTransactionAndAddComments(OrderPaymentInterface $payment, ?InvoiceInterface $invoice, array $validParams, float $amount, OrderInterface $order): void
    {
        $message = $this->stateCommand->execute($payment, $amount, $order);
        $payment->setTransactionId($validParams['tr_id'])
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $validParams);

        $transaction = $payment->addTransaction(Transaction::TYPE_ORDER, $invoice, true);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);

        $this->orderPaymentRepository->save($payment);
        $this->orderRepository->save($order);
    }

    private function createCardTransactionAndAddComments(OrderPaymentInterface $payment, ?InvoiceInterface $invoice, array $validParams, float $amount, OrderInterface $order): void
    {
        $payment
            ->setTransactionId($validParams['sale_auth'])
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $validParams);

        $transaction = $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, $invoice, true);
        $message = $this->stateCommand->execute($payment, $amount, $order);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);

        $this->orderPaymentRepository->save($payment);
        $this->orderRepository->save($order);
    }

    private function registerCardCaptureNotificationTpay(OrderPaymentInterface $payment, $amount, $validParams, $skipFraudDetection = false)
    {
        $this->setTransactionIdForPayment($payment);

        $order = $payment->getOrder();
        $amount = (float)$amount;
        $invoice = $this->getInvoiceForTransactionId($order, $payment->getTransactionId());
        $orderCurrencyCode = $order->getOrderCurrency()->getCode();

        if (!in_array($orderCurrencyCode, CurrencyCodesDictionary::CODES)) {
            throw new Exception(sprintf('Order currency %s does not exist in Tpay dictionary!', $orderCurrencyCode));
        }

        $orderCurrency = array_search($orderCurrencyCode, CurrencyCodesDictionary::CODES);

        if (!$invoice && $payment->isCaptureFinal($amount) && ($orderCurrency === (int)$validParams['currency'] || $orderCurrencyCode === $validParams['currency'])) {
            $invoice = $this->createAndRegisterInvoice($order, $payment, Order::STATE_PROCESSING);
        } else {
            $this->handlePotentialFraud($payment, $amount, $skipFraudDetection);
        }

        $this->finalizePayment($payment, $invoice, $amount, $order);
        $this->createCardTransactionAndAddComments($payment, $invoice, $validParams, $amount, $order);
    }

    private function updateTransactionId(Order $order, array $validParams): Order
    {
        if (isset($validParams['transactionId'])) {
            $additionalInfo = $order->getPayment()->getAdditionalInformation();
            $additionalInfo['transaction_id'] = $validParams['transactionId'];
            $order->getPayment()->setAdditionalInformation($additionalInfo);
        }

        return $order;
    }

    private function determineOrderStatus(OrderInterface $order, array $validParams, bool &$emailNotify): ?string
    {
        $orderAmount = (float)number_format((float)$order->getBaseGrandTotal(), 2, '.', '');
        $trStatus = $validParams['tr_status'];

        if ('TRUE' === $trStatus && ((float)number_format($validParams['tr_paid'], 2, '.', '') === $orderAmount)) {
            if (Order::STATE_PROCESSING !== $order->getState()) {
                $emailNotify = true;
            }

            $this->registerCaptureNotificationTpay($order->getPayment(), $order->getBaseGrandTotal(), $validParams);

            return Order::STATE_PROCESSING;
        } elseif ('CHARGEBACK' === $trStatus) {
            $order->addCommentToStatusHistory($this->getTransactionDesc($validParams));
            $this->orderRepository->save($order);

            return null;
        }

        if (Order::STATE_HOLDED !== $order->getState()) {
            $emailNotify = true;
        }

        $comment = __('The order has been holded: ') . '</br>' . $this->getTransactionDesc($validParams);
        $status = Order::STATE_HOLDED;
        $order->addStatusToHistory($status, $comment, true);

        return $status;
    }

    private function commentCardOrder(OrderInterface &$order, array $validParams, string $orderId): bool
    {
        $transactionDesc = $this->getCardTransactionDesc($validParams);
        $orderAmount = (float)number_format((float)$order->getBaseGrandTotal(), 2, '.', '');
        $emailNotify = false;

        $order = $this->updateTransactionId($order, $validParams);
        $amountCheck = (float)number_format((float)$validParams['amount'], 2, '.', '') !== $orderAmount;

        if (!isset($validParams['status']) || 'correct' !== $validParams['status'] || $amountCheck) {
            $comment = __('Payment has been declined. ') . '</br>' . $transactionDesc;
            $this->addCommentToHistory($orderId, $comment);
        } else {
            if (Order::STATE_PROCESSING != $order->getState()) {
                $emailNotify = true;
            }

            $this->registerCardCaptureNotificationTpay($order->getPayment(), $order->getBaseGrandTotal(), $validParams);
        }

        return $emailNotify;
    }

    private function updateOrderStatus(OrderInterface $order, string $status, bool $emailNotify): OrderInterface
    {
        if ($emailNotify) {
            $order->setSendEmail(true);
        }

        $order->setStatus($status)->setState($status);
        $this->orderRepository->save($order);

        return $order;
    }

    private function sendInvoiceEmailsIfNeeded(OrderInterface $order, TpayConfigInterface $tpayConfig): void
    {
        if ($tpayConfig->getInvoiceSendMail()) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceId = $invoice->getId();
                $this->invoiceService->notify($invoiceId);
            }
        }
    }
}
