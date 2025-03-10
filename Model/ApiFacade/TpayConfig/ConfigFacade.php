<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;

class ConfigFacade
{
    /** @var ConfigOrigin */
    private $originConfig;

    /** @var ConfigOpen */
    private $openApi;

    /** @var CardConfigFacade */
    private $cardConfig;

    /** @var bool */
    private $useOpenApi;

    public function __construct(CardConfigFacade $cardConfig, ConfigOpen $configOpen, ConfigOrigin $configOrigin, ScopeConfigInterface $storeConfig)
    {
        $this->cardConfig = $cardConfig;
        $this->openApi = $configOpen;
        $this->originConfig = $configOrigin;
        $this->useOpenApi = $storeConfig->isSetFlag('payment/tpaycom_magento2basic/openapi_settings/open_api_active', ScopeInterface::SCOPE_STORE);
    }

    public function getConfig(): array
    {
        return array_merge($this->getCurrentApi() ? $this->getCurrentApi()->getConfig() : [], $this->cardConfig->getConfig());
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originConfig;
    }
}
