<?php

namespace Tpay\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Throwable;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApiFactory;

class OrderCancel implements ObserverInterface
{
    /** @var OpenApiFactory */
    private $openApiFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        OpenApiFactory $openApiFactory,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->openApiFactory = $openApiFactory;
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        $payment = $order->getPayment();
        if (TpayInterface::CODE !== $payment->getMethod()) {
            return;
        }
        try {
            $api = $this->openApiFactory->create(['storeId' => $order->getStoreId()]);
            $transactionId = $payment->getAdditionalInformation('transaction_id');
            $this->logger->info('Tpay transaction cancellation attempt', ['transactionId' => $transactionId]);
            if ($transactionId) {
                $api->cancel($transactionId);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Tpay transaction cancellation failed', ['reason' => $e->getMessage(), 'transactionId' => $transactionId ?? null, 'orderId' => $order->getId(), 'exception' => $e]);
        }
    }
}
