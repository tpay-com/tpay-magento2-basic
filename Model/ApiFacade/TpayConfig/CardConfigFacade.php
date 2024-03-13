<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
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

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager, TpayService $tpayService)
    {
        $this->createOriginApiInstance($tpay, $tpayConfig, $assetRepository, $tokensService, $tpayService);
        $this->createOpenApiInstance($tpay, $tpayConfig, $assetRepository, $tokensService, $storeManager);
    }

    public function getConfig(): array
    {
        return $this->getCurrentApi() ? $this->getCurrentApi()->getCardConfig() : [];
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOriginApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, TpayService $tpayService)
    {
        if (!$tpayConfig->isCardEnabled()) {
            $this->originApi = null;

            return;
        }

        try {
            new CardOrigin($tpay, $tpayConfig, $tokensService, $tpayService);
            $this->originApi = new ConfigOrigin($tpay, $tpayConfig, $assetRepository, $tokensService);
        } catch (Exception $exception) {
            $this->originApi = null;
        }
    }

    private function createOpenApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        if ('PLN' !== $storeManager->getStore()->getCurrentCurrencyCode()) {
            $this->openApi = null;
            $this->useOpenApi = false;

            return;
        }

        try {
            $this->openApi = new ConfigOpen($tpay, $tpayConfig, $assetRepository, $tokensService);
            $this->openApi->authorization();
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
