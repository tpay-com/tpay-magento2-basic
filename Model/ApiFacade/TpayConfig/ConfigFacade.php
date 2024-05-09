<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
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

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var Repository */
    private $assetRepository;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var TpayService */
    private $tpayService;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager, TpayService $tpayService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
        $this->storeManager = $storeManager;
        $this->tpayService = $tpayService;
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
            $this->createOriginApiInstance($this->tpay, $this->tpayConfig, $this->assetRepository, $this->tokensService);
            $this->createOpenApiInstance($this->tpay, $this->tpayConfig, $this->assetRepository, $this->tokensService, $this->storeManager);
            $this->cardConfig = new CardConfigFacade($this->tpay, $this->tpayConfig, $this->assetRepository, $this->tokensService, $this->storeManager, $this->tpayService);
        }
    }

    private function createOriginApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService)
    {
        if (!$tpayConfig->isOriginApiEnabled()) {
            $this->originConfig = null;

            return;
        }

        try {
            new TransactionOriginApi($tpayConfig->getApiPassword(), $tpayConfig->getApiKey(), $tpayConfig->getMerchantId(), $tpayConfig->getSecurityCode(), !$tpayConfig->useSandboxMode());
            $this->originConfig = new ConfigOrigin($tpay, $tpayConfig, $assetRepository, $tokensService);
        } catch (Exception $exception) {
            $this->originConfig = null;
        }
    }

    private function createOpenApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        if ('PLN' !== $storeManager->getStore()->getBaseCurrencyCode() || !$tpayConfig->isOpenApiEnabled()) {
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
