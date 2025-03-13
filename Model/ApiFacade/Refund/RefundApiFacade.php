<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Store\Model\ScopeInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApi;

class RefundApiFacade
{
    /** @var RefundOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    /** @var RefundCardOriginApi */
    private $refundOriginApi;

    public function __construct(RefundCardOriginApi $refundOriginApi, RefundOriginApi $originApi, OpenApi $openApi, ScopeConfigInterface $storeConfig)
    {
        $this->originApi = $originApi;
        $this->refundOriginApi = $refundOriginApi;
        $this->openApi = $openApi;
        $this->useOpenApi = $storeConfig->isSetFlag('payment/tpaycom_magento2basic/openapi_settings/open_api_active', ScopeInterface::SCOPE_STORE);
    }

    public function makeRefund(InfoInterface $payment, float $amount)
    {
        if (false !== strpos($payment->getLastTransId(), '-')) {
            if ($payment->getAdditionalInformation('transaction_id')) {
                return $this->openApi->makeRefund($payment, $amount);
            }

            return $this->originApi->makeRefund($payment, $amount);
        }

        return $this->refundOriginApi->makeCardRefund($payment, $amount);
    }

    /** @return OpenApi|RefundOriginApi */
    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }
}
