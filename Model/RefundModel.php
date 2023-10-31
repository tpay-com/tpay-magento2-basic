<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use tpayLibs\src\_class_tpay\Refunds\BasicRefunds;
use tpayLibs\src\_class_tpay\Utilities\Util;

class RefundModel extends BasicRefunds
{
    public function __construct(string $apiPassword, string $apiKey, int $merchantId, string $merchantSecret, $isProd = true)
    {
        $this->trApiKey = $apiKey;
        $this->trApiPass = $apiPassword;
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
        if (!$isProd) {
            $this->apiURL = 'https://secure.sandbox.tpay.com/api/gw/';
        }
    }

    /** @throws \Exception */
    public function makeRefund(InfoInterface $payment, float $amount): bool
    {
        Util::$loggingEnabled = false;
        $apiResult = $this->setTransactionID($payment->getParentTransactionId())
            ->refundAny(number_format($amount, 2));
        if (array_key_exists('result', $apiResult) && 1 === (int) $apiResult['result']) {
            return true;
        }
        $errCode = array_key_exists('err', $apiResult) ? ' error code: '.$apiResult['err'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode));
    }
}
