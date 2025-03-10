<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\CardTransaction\CardOrigin;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;

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
