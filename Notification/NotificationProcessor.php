<?php

namespace Tpay\Magento2\Notification;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Model\CacheProvider;
use Tpay\Magento2\Service\TpayService;
use Tpay\OpenApi\Utilities\Cache;
use Tpay\OpenApi\Utilities\CacheCertificateProvider;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification as OpenApiWebhook;
use Tpay\OriginApi\Webhook\JWSVerifiedPaymentNotification as OriginApiWebhook;

class NotificationProcessor
{
    /** @var NotificationProcessorFactoryInterface */
    protected $factory;

    /** @var TpayService */
    protected $tpayService;

    /** @var RequestInterface */
    private $request;

    /** @var TpayConfigInterface */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CacheProvider */
    private $cacheProvider;

    public function __construct(
        RequestInterface $request,
        NotificationProcessorFactoryInterface $factory,
        TpayService $tpayService,
        TpayConfigInterface $config,
        StoreManagerInterface $storeManager,
        CacheProvider $cacheProvider
    ) {
        $this->request = $request;
        $this->factory = $factory;
        $this->tpayService = $tpayService;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->cacheProvider = $cacheProvider;
    }

    public function process()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $webhook = $this->createWebhook($storeId);

        $notification = $webhook->getNotification();

        $strategy = $this->factory->create($notification);

        $strategy->process($notification);
    }

    /** @return OpenApiWebhook|OriginApiWebhook */
    private function createWebhook(?int $storeId)
    {
        if (null !== $this->request->getPost('card')) {
            return new OriginApiWebhook(
                $this->config->getApiPassword($storeId),
                !$this->config->useSandboxMode($storeId)
            );
        }

        $certificateProvider = new CacheCertificateProvider(
            new Cache(null, $this->cacheProvider)
        );

        return new OpenApiWebhook(
            $certificateProvider,
            $this->config->getSecurityCode($storeId),
            !$this->config->useSandboxMode($storeId)
        );
    }
}
