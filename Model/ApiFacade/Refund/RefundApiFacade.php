<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Refund;

use Exception;
use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\OpenApi;

class RefundApiFacade
{
    /** @var TpayInterface */
    private $tpay;

    /** @var RefundOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay)
    {
        $this->tpay = $tpay;
        $this->originApi = new RefundOriginApi($tpay);
        $this->createOpenApiInstance($tpay->getOpenApiClientId(), $tpay->getOpenApiPassword(), !$tpay->useSandboxMode());
    }

    public function makeRefund(InfoInterface $payment, float $amount)
    {
        if ($payment->getAdditionalInformation('transaction_id')) {
            return $this->getCurrentApi()->makeRefund($payment, $amount);
        }
        if (!empty($payment->getAdditionalInformation('card_data'))) {
            return (new RefundCardOriginApi($this->tpay))->makeCardRefund($payment, $amount);
        }

        return $this->originApi->makeRefund($payment, $amount);
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