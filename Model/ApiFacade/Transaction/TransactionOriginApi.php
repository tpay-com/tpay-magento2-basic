<?php

namespace Tpay\Magento2\Model\ApiFacade\Transaction;

use Tpay\OriginApi\PaymentBlik;

class TransactionOriginApi extends PaymentBlik
{
    public const BLIK_CHANNEL = 150;

    /**
     * @param string $apiPassword
     * @param string $apiKey
     * @param int    $merchantId
     * @param string $merchantSecret
     * @param mixed  $isProd
     */
    public function __construct($apiPassword, $apiKey, $merchantId, $merchantSecret, $isProd = true)
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

    public function createTransaction(array $data): array
    {
        return $this->create($data);
    }
}
