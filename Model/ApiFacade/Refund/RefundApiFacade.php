<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Exception;
use Magento\Framework\App\CacheInterface;
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

    /** @var null|bool */
    private $storeId;

    private $cache;

    public function __construct(TpayConfigInterface $tpay, CacheInterface $cache, ?int $storeId = null)
    {
        $this->tpay = $tpay;
        $this->cache = $cache;
        $this->storeId = $storeId;
    }

    public function makeRefund(InfoInterface $payment, float $amount)
    {
        if ($payment->getAdditionalInformation('transaction_id')) {
            return $this->getCurrentApi()->makeRefund($payment, $amount);
        }
        if (!empty($payment->getAdditionalInformation('card_data'))) {
            return (new RefundCardOriginApi($this->tpay, $this->storeId))->makeCardRefund($payment, $amount);
        }

        return $this->originApi->makeRefund($payment, $amount);
    }

    private function getCurrentApi()
    {
        $this->connectApi();

        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function connectApi()
    {
        if (null == $this->openApi && null === $this->originApi) {
            $this->createRefundOriginApiInstance($this->tpay);
            $this->createOpenApiInstance($this->tpay);
        }
    }

    private function createRefundOriginApiInstance(TpayConfigInterface $tpay)
    {
        try {
            $this->originApi = new RefundOriginApi($tpay, $this->storeId);
        } catch (Exception $exception) {
            $this->originApi = null;
        }
    }

    private function createOpenApiInstance(TpayConfigInterface $tpay)
    {
        try {
            $this->openApi = new OpenApi($tpay, $this->cache, $this->storeId);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
