<?php

namespace tpaycom\magento2basic\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
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
    /**
     * @var TpayInterface
     */
    private $tpay;

    public function __construct(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService)
    {
        $this->tpay = $tpay;
        $this->originApi = new ConfigOrigin($tpay, $assetRepository, $tokensService);
        $this->createOpenApiInstance($tpay, $assetRepository, $tokensService);
    }

    public function getConfig(): array
    {
        return $this->getCurrentApi()->getConfig();
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOpenApiInstance(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService)
    {
        try {
            $this->openApi = new ConfigOpen($tpay, $assetRepository, $tokensService);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
