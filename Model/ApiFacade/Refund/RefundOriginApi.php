<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Refund;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use Tpay\OriginApi\Refunds\BasicRefunds;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;

class RefundOriginApi extends BasicRefunds
{
    public function __construct(TpayInterface $tpay)
    {
        $this->trApiKey = $tpay->getApiPassword();
        $this->trApiPass = $tpay->getApiKey();
        $this->merchantId = $tpay->getMerchantId();
        $this->merchantSecret = $tpay->getSecurityCode();
        parent::__construct();
        if ($tpay->useSandboxMode()) {
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
