<?php

namespace Tpay\Magento2\Notification;

use Tpay\Magento2\Notification\Strategy\BlikAliasNotificationProcessor;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Service\TpayService;

class NotificationProcessor
{
    /** @var NotificationProcessorFactoryInterface */
    protected $factory;

    /** @var TpayService */
    protected $tpayService;

    public function __construct(NotificationProcessorFactoryInterface $factory, TpayService $tpayService)
    {
        $this->factory = $factory;
        $this->tpayService = $tpayService;
    }

    public function process()
    {
        $strategy = $this->factory->create($_POST);

        if ($strategy instanceof BlikAliasNotificationProcessor) {
            $storeId = null;
        } else {
            $orderId = isset($_POST['order_id']) ? base64_decode($_POST['order_id']) : base64_decode($_POST['tr_crc']);
            $storeId = $this->getOrderStore($orderId);
        }

        $strategy->process($storeId);
    }

    private function getOrderStore(string $orderId): ?int
    {
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int) $order->getStoreId() : null;
    }
}
