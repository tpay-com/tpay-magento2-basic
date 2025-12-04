<?php

namespace Tpay\Magento2\Notification;

use Magento\Framework\App\RequestInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Notification\Strategy\BlikAliasNotificationProcessor;
use Tpay\Magento2\Service\TpayService;

class NotificationProcessor
{
    /** @var NotificationProcessorFactoryInterface */
    protected $factory;

    /** @var TpayService */
    protected $tpayService;

    /** @var RequestInterface */
    private $request;

    public function __construct(NotificationProcessorFactoryInterface $factory, TpayService $tpayService, RequestInterface $request)
    {
        $this->factory = $factory;
        $this->tpayService = $tpayService;
        $this->request = $request;
    }

    public function process()
    {
        $strategy = $this->factory->create($this->request->getPost()->toArray());
        $storeId = null;

        if (!$strategy instanceof BlikAliasNotificationProcessor) {
            $orderId = $this->getOrderId();
            if ($orderId) {
                $storeId = $this->getOrderStore($orderId);
            }
        }

        $strategy->process($storeId);
    }

    private function getOrderId(): ?string
    {
        $value = $this->request->getPost('order_id') ?? $this->request->getPost('tr_crc');

        if (null === $value) {
            return null;
        }

        return base64_decode($value);
    }

    private function getOrderStore(string $orderId): ?int
    {
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int) $order->getStoreId() : null;
    }
}
