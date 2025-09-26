<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Magento\Payment\Model\InfoInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApi;
use Tpay\Magento2\Model\ApiFacade\OpenApiFactory;

class RefundApiFacade
{
    /** @var RefundOriginApiFactory */
    private $originApi;

    /** @var OpenApiFactory */
    private $openApi;

    /** @var RefundCardOriginApiFactory */
    private $refundOriginApi;

    public function __construct(RefundCardOriginApiFactory $refundOriginApi, RefundOriginApiFactory $originApi, OpenApiFactory $openApi)
    {
        $this->originApi = $originApi;
        $this->refundOriginApi = $refundOriginApi;
        $this->openApi = $openApi;
    }

    public function makeRefund(InfoInterface $payment, float $amount)
    {
        $storeId = $payment->getOrder()->getStoreId();
        if (false !== strpos($payment->getParentTransactionId(), '-')) {
            if ($payment->getAdditionalInformation('transaction_id')) {
                /** @var OpenApi $openApi */
                $openApi = $this->openApi->create(['storeId' => $storeId]);

                return $openApi->makeRefund($payment, $amount);
            }

            /** @var RefundOriginApi $originApi */
            $originApi = $this->originApi->create(['storeId' => $storeId]);

            return $originApi->makeRefund($payment, $amount);
        }

        /** @var RefundCardOriginApi $refundOriginApi */
        $refundOriginApi = $this->refundOriginApi->create(['storeId' => $storeId]);

        return $refundOriginApi->makeCardRefund($payment, $amount);
    }
}
