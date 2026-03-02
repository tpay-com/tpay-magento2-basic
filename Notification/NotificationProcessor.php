<?php

namespace Tpay\Magento2\Notification;

use _PHPStan_5adafcbb8\Psr\Http\Message\RequestInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\OpenApi\Model\Objects\NotificationBody\BasicPayment;
use Tpay\OpenApi\Utilities\Cache;
use Tpay\OpenApi\Utilities\CacheCertificateProvider;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification as OpenApiWebhook;
use Tpay\OriginApi\Webhook\JWSVerifiedPaymentNotification as OriginApiWebhook;

class NotificationProcessor
{
    /** @var RequestInterface */
    private $request;

    /** @var NotificationProcessorFactoryInterface */
    protected $factory;

    /** @var TpayService */
    protected $tpayService;

    /** @var TpayConfigInterface */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        RequestInterface $request,
        NotificationProcessorFactoryInterface $factory,
        TpayService $tpayService,
        TpayConfigInterface $config,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->factory = $factory;
        $this->tpayService = $tpayService;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    public function process()
    {
        $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();
        $webhook = $this->createWebhook($defaultStoreId);

        $notification = $webhook->getNotification();
        $storeId = $this->resolveStoreId($notification, $defaultStoreId);

        if ($storeId !== $defaultStoreId) {
            $webhook = $this->createWebhook($storeId);
            $notification = $webhook->getNotification();
        }

        $strategy = $this->factory->create($notification);

        $strategy->process($storeId);
    }

    private function resolveStoreId($notification, int $defaultStoreId): ?int
    {
        if ($notification instanceof BasicPayment) {
            $value = $notification->tr_crc->getValue();
        } elseif (is_array($notification)) {
            $value = $notification['order_id'] ?? $notification['tr_crc'] ?? null;
        } else {
            return null;
        }

        if (!$value) {
            return null;
        }

        $orderId = base64_decode($value);
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int) $order->getStoreId() : $defaultStoreId;
    }

    /**
     * @return OriginApiWebhook|OpenApiWebhook
     */
    private function createWebhook(?int $storeId)
    {
        if (null !== $this->request->getPost('card')) {
            return new OriginApiWebhook(
                $this->config->getApiPassword($storeId),
                !$this->config->useSandboxMode($storeId)
            );
        }

        $certificateProvider = new CacheCertificateProvider(new Cache());
        return new OpenApiWebhook(
            $certificateProvider,
            $this->config->getSecurityCode($storeId),
            !$this->config->useSandboxMode($storeId)
        );
    }
}
