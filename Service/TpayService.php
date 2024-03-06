<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Exception;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
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

    /** @var BuilderInterface */
    protected $builder;

    /** @var InvoiceService */
    protected $invoiceService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        BuilderInterface $builder,
        CommandInterface $stateCommand,
        BuilderInterface $transactionBuilder,
        ManagerInterface $transactionManager,
        EventManagerInterface $eventManager,
        InvoiceService $invoiceService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
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
            ->setTotalDue($order->getGrandTotal())
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

    /**
     * @throws Exception
     *
     * @return bool|OrderInterface
     */
    public function setOrderStatus(string $orderId, array $validParams, TpayConfigInterface $tpayConfig)
    {
        $order = $this->getOrderById($orderId);

        if (!$order->getId()) {
            return false;
        }

        $sendNewInvoiceMail = (bool) $tpayConfig->getInvoiceSendMail();
        $orderAmount = (float) number_format((float) $order->getGrandTotal(), 2, '.', '');
        $trStatus = $validParams['tr_status'];
        $emailNotify = false;

        if ('TRUE' === $trStatus && ((float) number_format($validParams['tr_paid'], 2, '.', '') === $orderAmount)) {
            if (Order::STATE_PROCESSING !== $order->getState()) {
                $emailNotify = true;
            }
            $status = Order::STATE_PROCESSING;
            $this->registerCaptureNotificationTpay($order->getPayment(), $order->getGrandTotal(), $validParams);
        } elseif ('CHARGEBACK' === $trStatus) {
            $order->addCommentToStatusHistory($this->getTransactionDesc($validParams));
            $this->orderRepository->save($order);

            return $order;
        } else {
            if (Order::STATE_HOLDED !== $order->getState()) {
                $emailNotify = true;
            }
            $comment = __('The order has been holded: ').'</br>'.$this->getTransactionDesc($validParams);
            $status = Order::STATE_HOLDED;
            $order->addStatusToHistory($status, $comment, true);
        }

        if ($emailNotify) {
            $order->setSendEmail(true);
        }

        $order->setStatus($status)->setState($status);
        $this->orderRepository->save($order);
        if ($sendNewInvoiceMail) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceId = $invoice->getId();
                $this->invoiceService->notify($invoiceId);
            }
        }

        return $order;
    }

    public function getOrderById(string $orderId): OrderInterface
    {
        return $this->orderRepository->getByIncrementId($orderId);
    }

    public function setCardOrderStatus($orderId, array $validParams, TpayConfigInterface $tpayConfig)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        if (!$order->getId()) {
            return false;
        }

        $sendNewInvoiceMail = (bool) $tpayConfig->getInvoiceSendMail();
        $transactionDesc = $this->getCardTransactionDesc($validParams);
        $orderAmount = (float) number_format((float) $order->getGrandTotal(), 2, '.', '');
        $emailNotify = false;

        $order = $this->updateTransactionId($order, $validParams);
        $amountCheck = (float) number_format((float) $validParams['amount'], 2, '.', '') !== $orderAmount;

        if (!isset($validParams['status']) || 'correct' !== $validParams['status'] || $amountCheck) {
            $comment = __('Payment has been declined. ').'</br>'.$transactionDesc;
            $this->addCommentToHistory($orderId, $comment);
        } else {
            if (Order::STATE_PROCESSING != $order->getState()) {
                $emailNotify = true;
            }
            $this->registerCardCaptureNotificationTpay($order->getPayment(), $order->getGrandTotal(), $validParams);
        }

        if ($emailNotify) {
            $order->setSendEmail(true);
        }

        $this->orderRepository->save($order);

        if ($sendNewInvoiceMail) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceId = $invoice->getId();
                $this->invoiceService->notify($invoiceId);
            }
        }

        return $order;
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
        $transactionDesc = '<b>'.$validParams['tr_id'].'</b> ';
        $transactionDesc .= 'none' === $error ? ' ' : ' Error:  <b>'.strtoupper($error).'</b> ('.$paid.')';

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
            return 'Payment error: '.$validParams['err_desc'].', error code: '.$validParams['err_code'];
        }
        $error = null;
        if ('declined' === $validParams['status']) {
            $error = $validParams['reason'];
        }

        $transactionDesc = (is_null($error)) ? ' ' : ' Reason:  <b>'.$error.'</b>';
        $transactionDesc .= (isset($validParams['test_mode'])) && 1 === (int) $validParams['test_mode']
            ? '<b> TEST </b>'
            : ' ';

        return $transactionDesc;
    }

    /**
     * @param float|string $amount
     * @param bool|int     $skipFraudDetection
     */
    private function registerCaptureNotificationTpay(
        OrderPaymentInterface $payment,
        $amount,
        array $validParams,
        $skipFraudDetection = false
    ) {
        // @var $payment Payment
        $payment->setTransactionId(
            $this->transactionManager->generateTransactionId(
                $payment,
                Transaction::TYPE_CAPTURE,
                $payment->getAuthorizationTransaction()
            )
        );

        $order = $payment->getOrder();
        $amount = (float) $amount;
        $invoice = $this->getInvoiceForTransactionId($order, $payment->getTransactionId());

        if (!$invoice && $payment->isCaptureFinal($amount)) {
            $invoice = $order->prepareInvoice()->register();
            $invoice->setOrder($order);
            $order->addRelatedObject($invoice);
            $payment->setCreatedInvoice($invoice);
            $payment->setShouldCloseParentTransaction(true);
        } else {
            $payment->setIsFraudDetected(!$skipFraudDetection);
            $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
        }

        if (!$payment->getIsTransactionPending() && $invoice && Invoice::STATE_OPEN === $invoice->getState()) {
            $invoice->setOrder($order);
            $invoice->pay();
            $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
            $order->addRelatedObject($invoice);
        }

        $message = $this->stateCommand->execute($payment, $amount, $order);
        $payment
            ->setTransactionId($validParams['tr_id'])
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $validParams);

        $transaction = $payment->addTransaction(Transaction::TYPE_ORDER, $invoice, true);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);
    }

    private function registerCardCaptureNotificationTpay(
        OrderPaymentInterface $payment,
        $amount,
        $validParams,
        $skipFraudDetection = false
    ) {
        // @var $payment Payment
        $payment->setTransactionId(
            $this->transactionManager->generateTransactionId(
                $payment,
                Transaction::TYPE_CAPTURE,
                $payment->getAuthorizationTransaction()
            )
        );

        $order = $payment->getOrder();
        $amount = (float) $amount;
        $invoice = $this->getInvoiceForTransactionId($order, $payment->getTransactionId());
        $orderCurrencyCode = $order->getOrderCurrency()->getCode();

        if (!in_array($orderCurrencyCode, CurrencyCodesDictionary::CODES)) {
            throw new Exception(sprintf('Order currency %s does not exist in Tpay dictionary!', $orderCurrencyCode));
        }
        $orderCurrency = array_search($orderCurrencyCode, CurrencyCodesDictionary::CODES);
        // register new capture
        if (
            !$invoice
            && $payment->isCaptureFinal($amount)
            && ($orderCurrency === (int) $validParams['currency'] || $orderCurrencyCode === $validParams['currency'])
        ) {
            $invoice = $order->prepareInvoice()->register();
            $invoice->setOrder($order);
            $order->addRelatedObject($invoice);
            $payment->setCreatedInvoice($invoice);
            $payment->setShouldCloseParentTransaction(true);
            $order->setState(Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
        } else {
            $payment->setIsFraudDetected(!$skipFraudDetection);
            $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
        }

        if (!$payment->getIsTransactionPending() && $invoice && Invoice::STATE_OPEN === $invoice->getState()) {
            $invoice->setOrder($order);
            $invoice->pay();
            $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
            $order->addRelatedObject($invoice);
        }

        $payment
            ->setTransactionId($validParams['sale_auth'])
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $validParams);

        $transaction = $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, $invoice, true);
        $message = $this->stateCommand->execute($payment, $amount, $order);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);
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
}
