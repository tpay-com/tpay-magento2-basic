<?php

namespace tpaycom\magento2basic\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Service\TpayTokensService;

class ConfigFacade
{
    /** @var ConfigOrigin */
    private $originApi;

    /** @var ConfigOpen */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        $this->createOriginApiInstance($tpay, $assetRepository, $tokensService);
        $this->createOpenApiInstance($tpay, $assetRepository, $tokensService, $storeManager);
    }

    public function getConfig(): array
    {
        return $this->getCurrentApi()->getConfig();
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOriginApiInstance(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService)
    {
        try {
            $this->originApi = new ConfigOrigin($tpay, $assetRepository, $tokensService);
        } catch (Exception $exception) {
            $this->originApi = null;
        }
    }

    private function createOpenApiInstance(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        if ('PLN' !== $storeManager->getStore()->getCurrentCurrencyCode()) {
            $this->openApi = null;
            $this->useOpenApi = false;

            return;
        }

        try {
            $this->openApi = new ConfigOpen($tpay, $assetRepository, $tokensService);
            $this->openApi->authorization();
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
