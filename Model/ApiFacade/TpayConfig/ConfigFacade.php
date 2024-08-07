<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;

class ConfigFacade
{
    /** @var ConfigOrigin\Proxy */
    private $originConfig;

    /** @var ConfigOpen\Proxy */
    private $openApi;

    /** @var CardConfigFacade\Proxy */
    private $cardConfig;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var bool */
    private $useOpenApi;

    public function __construct(
        TpayConfigInterface $tpayConfig,
        ConfigOrigin\Proxy $originConfig,
        ConfigOpen\Proxy $openApi,
        CardConfigFacade\Proxy $cardConfig
    ) {
        $this->tpayConfig = $tpayConfig;
        $this->originConfig = $originConfig;
        $this->openApi = $openApi;
        $this->cardConfig = $cardConfig;
    }

    public function getConfig(): array
    {
        $this->connectApi();

        return array_merge($this->getCurrentApi() ? $this->getCurrentApi()->getConfig() : [], $this->cardConfig->getConfig());
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originConfig;
    }

    private function connectApi()
    {
        if (null == $this->openApi && null === $this->originConfig) {
            $this->createOriginApiInstance($this->tpayConfig);
            $this->createOpenApiInstance($this->tpayConfig);
        }
    }

    private function createOriginApiInstance(TpayConfigInterface $tpayConfig)
    {
        if (!$tpayConfig->isOriginApiEnabled()) {
            return;
        }

        try {
            new TransactionOriginApi($tpayConfig->getApiPassword(), $tpayConfig->getApiKey(), $tpayConfig->getMerchantId(), $tpayConfig->getSecurityCode(), !$tpayConfig->useSandboxMode());
        } catch (Exception $exception) {
            return;
        }
    }

    private function createOpenApiInstance(TpayConfigInterface $tpayConfig)
    {
        if (!$tpayConfig->isPlnPayment() || !$tpayConfig->isOpenApiEnabled()) {
            $this->openApi = null;
            $this->useOpenApi = false;

            return;
        }

        try {
            $this->openApi->authorization();
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->useOpenApi = false;
        }
    }
}
