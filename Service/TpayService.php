<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Tpay\Magento2\Api\Sales\OrderRepositoryInterface;

class TpayService
{
    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderPaymentRepositoryInterface */
    protected $orderPaymentRepository;

    /** @var InvoiceService */
    protected $invoiceService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        InvoiceService $invoiceService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->invoiceService = $invoiceService;
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

    public function confirmPayment(OrderInterface $order, float $amount, string $transactionId, array $params): void
    {
        if ($order->canInvoice()) {
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $params);
            $payment->registerCaptureNotification($amount);
            $this->orderRepository->save($order);
        }
    }
}
