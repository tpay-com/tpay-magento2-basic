<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
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

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var Repository */
    private $assetRepository;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var TpayService */
    private $tpayService;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService, TpayService $tpayService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
        $this->tpayService = $tpayService;
    }

    public function getConfig(): array
    {
        $this->connectApi();

        return $this->getCurrentApi() ? $this->getCurrentApi()->getCardConfig() : [];
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function connectApi()
    {
        if (null == $this->openApi && null === $this->originApi) {
            $this->createOriginApiInstance($this->tpay, $this->tpayConfig, $this->assetRepository, $this->tokensService, $this->tpayService);
            $this->createOpenApiInstance($this->tpay, $this->tpayConfig, $this->assetRepository, $this->tokensService);
        }
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

    private function createOpenApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService)
    {
        if (!$tpayConfig->isPlnPayment()) {
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
