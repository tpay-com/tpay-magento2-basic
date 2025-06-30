<?php

namespace Tpay\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApiFactory;

class OrderCancel implements ObserverInterface
{
    public function __construct(
        private readonly OpenApiFactory $openApiFactory,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        $payment = $order->getPayment();
        if (TpayInterface::CODE !== $payment->getMethod()) {
            return;
        }
        $api = $this->openApiFactory->create(['storeId' => $order->getStoreId()]);
        $transactionId = $payment->getAdditionalInformation('transaction_id');
        $this->logger->info('Tpay transaction cancellation attempt', ['transactionId' => $transactionId]);
        if ($transactionId) {
            $api->cancel($transactionId);
        }
    }
}
