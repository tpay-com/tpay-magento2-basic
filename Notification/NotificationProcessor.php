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
    /**
     * @var RequestInterface
     */
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

        if ($strategy instanceof BlikAliasNotificationProcessor) {
            $storeId = null;
        } else {
            $orderId = $this->request->getPost('order_id') ? base64_decode($this->request->getPost('order_id')) : base64_decode($this->request->getPost('tr_crc'));
            $storeId = $this->getOrderStore($orderId);
        }

        $strategy->process($storeId);
    }

    private function getOrderStore(string $orderId): ?int
    {
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int)$order->getStoreId() : null;
    }
}
