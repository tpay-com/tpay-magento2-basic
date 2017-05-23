<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Service;

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

        $order->addStatusToHistory(
            Order::STATE_PENDING_PAYMENT,
            __('Waiting for tpay.com payment.')
        );
        $order->setTotalDue($order->getGrandTotal())->setTotalPaid(0.00);
        $order->setSendEmail($sendEmail);
        $order->save();

        return $order;
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
     * @return bool|Order
     */
    public function validateOrderAndSetStatus($orderId, array $validParams)
    {
        /** @var Order $order */
        $order = $this->orderRepository->getByIncrementId($orderId);

        if (!$order->getId()) {
            return false;
        }

        $payment = $order->getPayment();
        $transactionDesc = $this->getTransactionDesc($validParams);
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

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $transactionDesc
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $transaction->save();
        }

        $orderAmount = (double)number_format($order->getGrandTotal(), 2);

        $trStatus = $validParams[ResponseFields::TR_STATUS];
        $emailNotify = false;

        if ($trStatus === 'TRUE' && ((double)number_format($validParams[ResponseFields::TR_PAID],
                    2) === $orderAmount)
        ) {
            if ($order->getState() != Order::STATE_PROCESSING) {
                $emailNotify = true;
            }
            $status = __('The payment from tpay.com has been accepted.') . '</br>' . $transactionDesc;
            $state = Order::STATE_PROCESSING;
            $order->setTotalDue(0.00)->setTotalPaid((double)$validParams[ResponseFields::TR_PAID]);
        } else {
            if ($order->getState() != Order::STATE_HOLDED) {
                $emailNotify = true;
            }
            $status = __('Payment has been canceled: ') . '</br>' . $transactionDesc;
            $state = Order::STATE_HOLDED;
        }

        $order->setState($state);
        $order->addStatusToHistory($state, $status, true);

        if ($emailNotify) {
            $order->setSendEmail(true);
        }

        $order->save();

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
}
