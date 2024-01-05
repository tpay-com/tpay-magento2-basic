<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Transaction;

use Exception;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\OpenApi;

class TransactionApiFacade
{
    /** @var TransactionOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay)
    {
        $this->originApi = new TransactionOriginApi($tpay->getApiPassword(), $tpay->getApiKey(), $tpay->getMerchantId(), $tpay->getSecurityCode(), !$tpay->useSandboxMode());
        $this->createOpenApiInstance($tpay->getOpenApiClientId(), $tpay->getOpenApiPassword(), !$tpay->useSandboxMode());
    }

    public function isOpenApiUse()
    {
        return $this->useOpenApi;
    }

    public function create(array $config)
    {
        return $this->getCurrentApi()->create($config);
    }

    public function blik($blikTransactionId, $blikCode)
    {
        return $this->originApi->blik($blikTransactionId, $blikCode);
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOpenApiInstance(string $clientId, string $apiPassword, bool $isProd)
    {
        try {
            $this->openApi = new OpenApi($clientId, $apiPassword, $isProd);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
