<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Exception;
use Magento\Payment\Model\InfoInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApi;

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

    public function __construct(TpayConfigInterface $tpay)
    {
        $this->tpay = $tpay;
        $this->createRefundOriginApiInstance($tpay);
        $this->createOpenApiInstance($tpay);
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

    private function createRefundOriginApiInstance(TpayConfigInterface $tpay)
    {
        try {
            $this->originApi = new RefundOriginApi($tpay);
        } catch (Exception $exception) {
            $this->originApi = null;
        }
    }

    private function createOpenApiInstance(TpayConfigInterface $tpay)
    {
        try {
            $this->openApi = new OpenApi($tpay);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
