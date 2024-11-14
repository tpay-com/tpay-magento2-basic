<?php

namespace Tpay\Magento2\Notification;

use Tpay\Magento2\Notification\Strategy\Factory\NotificationProcessorFactoryInterface;
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

    public function process(string $orderId)
    {
        $strategy = $this->factory->create($_POST);

        $strategy->process($this->getOrderStore($orderId));
    }

    private function getOrderStore(string $orderId): ?int
    {
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int) $order->getStoreId() : null;
    }
}
