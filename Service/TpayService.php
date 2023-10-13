<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Service;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Operations\RegisterCaptureNotificationOperation;
use Magento\Sales\Model\Order\Payment\State\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;

class TpayService extends RegisterCaptureNotificationOperation
{
    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    protected $builder;
    protected $invoiceService;
    private $objectManager;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $builder,
        CommandInterface $stateCommand,
        BuilderInterface $transactionBuilder,
        ManagerInterface $transactionManager,
        EventManagerInterface $eventManager,
        InvoiceService $invoiceService
    ) {
        $this->orderRepository = $orderRepository;
        $this->builder = $builder;
        $this->objectManager = ObjectManager::getInstance();
        $this->invoiceService = $invoiceService;
        parent::__construct(
            $stateCommand,
            $transactionBuilder,
            $transactionManager,
            $eventManager
        );
    }

    /** Change order state and notify user if needed */
    public function setOrderStatePendingPayment(int $orderId, bool $sendEmail): Order
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        $order->setTotalDue($order->getGrandTotal())
            ->setTotalPaid(0.00)
            ->setBaseTotalPaid(0.00)
            ->setBaseTotalDue($order->getBaseGrandTotal())
            ->setState(Order::STATE_PENDING_PAYMENT)
            ->addStatusToHistory(true);

        $order->setSendEmail($sendEmail)->save();

        return $order;
    }

    public function addCommentToHistory($orderId, $comment)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);
        $order->addStatusToHistory($order->getState(), $comment);
        $order->save();
    }

    /** Return payment data */
    public function getPayment(int $orderId): OrderPaymentInterface
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        return $order->getPayment();
    }

    /**
     * Validate order and set appropriate state
     *
     * @return bool|Order
     */
    public function SetOrderStatus(int $orderId, array $validParams, TpayInterface $tpayModel)
    {
        $order = $this->getOrderById($orderId);
        if (!$order->getId()) {
            return false;
        }
        $sendNewInvoiceMail = (bool) $tpayModel->getInvoiceSendMail();
        $orderAmount = (float) number_format($order->getGrandTotal(), 2, '.', '');
        $trStatus = $validParams['tr_status'];
        $emailNotify = false;

        if (
            'TRUE' === $trStatus
            && ((float) number_format($validParams['tr_paid'], 2, '.', '') === $orderAmount)
        ) {
            if (Order::STATE_PROCESSING !== $order->getState()) {
                $emailNotify = true;
            }
            $status = Order::STATE_PROCESSING;
            $this->registerCaptureNotificationTpay($order->getPayment(), $order->getGrandTotal(), $validParams);
        } elseif ('CHARGEBACK' === $trStatus) {
            $order->addCommentToStatusHistory($this->getTransactionDesc($validParams));
            $order->save();

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
        $order->setStatus($status)->setState($status)->save();
        if ($sendNewInvoiceMail) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceId = $invoice->getId();
                $this->invoiceService->notify($invoiceId);
            }
        }

        return $order;
    }

    /** Get Order object by orderId */
    public function getOrderById(int $orderId): Order
    {
        return $this->orderRepository->getByIncrementId($orderId);
    }

    /**
     * Get description for transaction
     *
     * @return bool|string
     */
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

    /**
     * Registers capture notification.
     *
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
        $orderCurrency = $order->getOrderCurrency()->getCode();
        // register new capture

        if (!$invoice && 'PLN' === $orderCurrency && $payment->isCaptureFinal($amount)) {
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
        $payment->setTransactionId($validParams['tr_id'])
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $validParams);
        $transaction = $payment->addTransaction(
            Transaction::TYPE_ORDER,
            $invoice,
            true
        );
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);
    }
}
