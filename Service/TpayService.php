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
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\lib\ResponseFields;

/**
 * Class TpayService
 *
 * @package tpaycom\magento2basic\Service
 */
class TpayService
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    protected $builder;
    private $objectManager;

    /**
     * Tpay constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param BuilderInterface $builder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $builder
    ) {
        $this->orderRepository = $orderRepository;
        $this->builder = $builder;
        $this->objectManager = ObjectManager::getInstance();
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
            ->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
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
     * @param $tpayModel
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
            $this->setInvoice($order, $sendNewInvoiceMail);
            $this->setTransaction($order, $validParams);

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
     * @param OrderInterface $order
     * @param bool $sendMail
     * @throws LocalizedException
     */
    private function setInvoice($order, $sendMail)
    {
        if ($order->canInvoice()) {
            // Create invoice for this order
            $invoice = $this->objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);

            // Make sure there is a qty on the invoice
            if (!$invoice->getTotalQty()) {
                throw new LocalizedException(
                    __('You can\'t create an invoice without products.')
                );
            }

            // Register as invoice item
            $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE)->register();

            if ($sendMail) {
                $this->objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')->send($invoice);
            }

            $order->save();
            $transaction = $this->objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
        }
    }

    /**
     * @param OrderInterface $order
     * @param array $validParams
     */
    private function setTransaction($order, $validParams)
    {
        $payment = $order->getPayment();
        if ($payment) {
            $payment->setLastTransId($validParams[ResponseFields::TR_ID]);
            $payment->setTransactionId($validParams[ResponseFields::TR_ID]);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$validParams]
            );
            $trans = $this->builder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($validParams[ResponseFields::TR_ID])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$validParams]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

            $payment->setParentTransactionId(null)->registerCaptureNotification($order->getGrandTotal());
            $payment->save();
            $transaction->save();
            foreach ($order->getRelatedObjects() as $object) {
                $object->save();
            }
        }
    }
}
