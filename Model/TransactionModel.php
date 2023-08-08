<?php

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\PaymentBlik;

/**
 * Class Transaction
 */
class TransactionModel extends PaymentBlik
{
    const BLIK_CHANNEL = 150;

    /**
     * Transaction constructor.
     *
     * @param string $apiPassword
     * @param string $apiKey
     * @param int    $merchantId
     * @param string $merchantSecret
     */
    public function __construct($apiPassword, $apiKey, $merchantId, $merchantSecret)
    {
        $this->trApiKey = $apiKey;
        $this->trApiPass = $apiPassword;
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
    }
}
