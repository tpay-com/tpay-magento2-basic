<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Service;

use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\lib\ResponseFields;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Operations\RegisterCaptureNotificationOperation;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Model\Order\Payment\State\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Class TpayService
 *
 * @package tpaycom\magento2basic\Service
 */
class TpayService extends RegisterCaptureNotificationOperation
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    protected $builder;

    protected $invoiceService;

    private $objectManager;

    /**
     * TpayBasic constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param BuilderInterface $builder
     * @param CommandInterface $stateCommand
     * @param BuilderInterface $transactionBuilder
     * @param ManagerInterface $transactionManager
     * @param EventManagerInterface $eventManager
     * @param InvoiceService $invoiceService
     */
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
            $eventManager);
    }

    /**
     * Change order state and notify user if needed
     *
     * @param int $orderId
     * @param bool $sendEmail
     *
     * @return Order
     */
    public function setOrderStatePendingPayment($orderId, $sendEmail)
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

    /**
     * Return payment data
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getPaymentData($orderId)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        return $order->getPayment()->getData();
    }

    /**
     * Validate order and set appropriate state
     *
     * @param int $orderId
     * @param array $validParams
     *
     * @param TpayInterface $tpayModel
     * @return bool|Order
     */
    public function SetOrderStatus($orderId, array $validParams, $tpayModel)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);
        if (!$order->getId()) {
            return false;
        }
        $sendNewInvoiceMail = (bool)$tpayModel->getInvoiceSendMail();
        $transactionDesc = $this->getTransactionDesc($validParams);
        $orderAmount = (double)number_format($order->getGrandTotal(), 2, '.', '');
        $trStatus = $validParams[ResponseFields::TR_STATUS];
        $emailNotify = false;

        if ($trStatus === 'TRUE' && ((double)number_format($validParams[ResponseFields::TR_PAID],
                    2, '.', '') === $orderAmount)
        ) {
            if ($order->getState() != Order::STATE_PROCESSING) {
                $emailNotify = true;
            }
            $state = Order::STATE_PROCESSING;
            $this->registerCaptureNotificationTpay($order->getPayment(), $order->getGrandTotal(), $validParams);
        } else {
            if ($order->getState() != Order::STATE_HOLDED) {
                $emailNotify = true;
            }
            $status = __('Payment has been canceled: ') . '</br>' . $transactionDesc;
            $state = Order::STATE_HOLDED;
            $order->addStatusToHistory($state, $status, true);
        }
        if ($emailNotify) {
            $order->setSendEmail(true);
        }
        $order->setState($state)->save();
        if ($sendNewInvoiceMail) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoice_id = $invoice->getIncrementId();
                $this->invoiceService->notify($invoice_id);
            }
        }
        return $order;
    }

    /**
     * Get description for transaction
     *
     * @param array $validParams
     *
     * @return bool|string
     */
    protected function getTransactionDesc(array $validParams)
    {
        if ($validParams === false) {
            return false;
        }

        $error = $validParams[ResponseFields::TR_ERROR];
        $paid = $validParams[ResponseFields::TR_PAID];
        $transactionDesc = '<b>' . $validParams[ResponseFields::TR_ID] . '</b> ';
        $transactionDesc .= $error === 'none' ? ' ' : ' Error:  <b>' . strtoupper($error) . '</b> (' . $paid . ')';

        return $transactionDesc . $validParams[ResponseFields::TEST_MODE] === '1' ? '<b> TEST </b>' : ' ';
    }

    /**
     * Registers capture notification.
     *
     * @param OrderPaymentInterface $payment
     * @param string|float $amount
     * @param array $validParams
     * @param bool|int $skipFraudDetection
     */
    private function registerCaptureNotificationTpay(
        OrderPaymentInterface $payment,
        $amount,
        $validParams,
        $skipFraudDetection = false
    ) {
        /**
         * @var $payment Payment
         */
        $payment->setTransactionId(
            $this->transactionManager->generateTransactionId(
                $payment,
                Transaction::TYPE_CAPTURE,
                $payment->getAuthorizationTransaction()
            )
        );

        $order = $payment->getOrder();
        $amount = (double)$amount;
        $invoice = $this->getInvoiceForTransactionId($order, $payment->getTransactionId());

        // register new capture
        if (!$invoice) {
            if ($payment->isSameCurrency() && $payment->isCaptureFinal($amount)) {
                $invoice = $order->prepareInvoice()->register();
                $invoice->setOrder($order);
                $order->addRelatedObject($invoice);
                $payment->setCreatedInvoice($invoice);
                $payment->setShouldCloseParentTransaction(true);
            } else {
                $payment->setIsFraudDetected(!$skipFraudDetection);
                $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
            }
        }

        if (!$payment->getIsTransactionPending()) {
            if ($invoice && Invoice::STATE_OPEN == $invoice->getState()) {
                $invoice->setOrder($order);
                $invoice->pay();
                $this->updateTotals($payment, ['base_amount_paid_online' => $amount]);
                $order->addRelatedObject($invoice);
            }
        }

        $message = $this->stateCommand->execute($payment, $amount, $order);
        $payment->setTransactionId($validParams[ResponseFields::TR_ID])
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
