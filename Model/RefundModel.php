<?php

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\Refunds\BasicRefunds;

/**
 * Class Refund
 */
class RefundModel extends BasicRefunds
{
    /**
     * Refund constructor.
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
