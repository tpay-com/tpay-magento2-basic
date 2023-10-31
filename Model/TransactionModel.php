<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\PaymentBlik;

class TransactionModel extends PaymentBlik
{
    public const BLIK_CHANNEL = 150;

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
}
