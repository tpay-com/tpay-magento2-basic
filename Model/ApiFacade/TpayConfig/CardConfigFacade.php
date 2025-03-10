<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CardConfigFacade
{
    /** @var ConfigOrigin */
    private $originApi;

    /** @var ConfigOpen */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(ConfigOrigin $originApi, ConfigOpen $openApi, ScopeConfigInterface $storeConfig)
    {
        $this->originApi = $originApi;
        $this->openApi = $openApi;
        $this->useOpenApi = $storeConfig->isSetFlag('payment/tpaycom_magento2basic/openapi_settings/open_api_active', ScopeInterface::SCOPE_STORE);
    }

    public function getConfig(): array
    {
        return $this->getCurrentApi() ? $this->getCurrentApi()->getCardConfig() : [];
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }
}
