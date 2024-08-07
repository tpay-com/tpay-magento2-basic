<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\OriginApi\Refunds\BasicRefunds;
use Tpay\OriginApi\Utilities\Util;

class RefundOriginApi extends BasicRefunds
{
    public function __construct(TpayConfigInterface $tpay, ?int $storeId = null)
    {
        $this->trApiKey = $tpay->getApiPassword($storeId);
        $this->trApiPass = $tpay->getApiKey($storeId);
        $this->merchantId = $tpay->getMerchantId($storeId);
        $this->merchantSecret = $tpay->getSecurityCode($storeId);
        parent::__construct();
        if ($tpay->useSandboxMode($storeId)) {
            $this->apiURL = 'https://secure.sandbox.tpay.com/api/gw/';
        }
    }

    public function makeRefund(InfoInterface $payment, float $amount): bool
    {
        Util::$loggingEnabled = false;
        $apiResult = $this->setTransactionID($payment->getParentTransactionId())->refundAny(number_format($amount, 2));
        if (isset($apiResult['result']) && 1 === (int) $apiResult['result']) {
            return true;
        }
        $errCode = isset($apiResult['err']) ? ' error code: '.$apiResult['err'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode));
    }
}
