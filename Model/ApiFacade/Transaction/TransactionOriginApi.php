<?php

namespace Tpay\Magento2\Model\ApiFacade\Transaction;

use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\OriginApi\PaymentBlik;

class TransactionOriginApi extends PaymentBlik
{
    public const BLIK_CHANNEL = 150;

    public function __construct(TpayConfigInterface $tpay, ?int $storeId = null)
    {
        $this->trApiKey = $tpay->getApiKey($storeId);
        $this->trApiPass = $tpay->getApiPassword($storeId);
        $this->merchantId = $tpay->getMerchantId($storeId);
        $this->merchantSecret = $tpay->getSecurityCode($storeId);
        parent::__construct();
        if ($tpay->useSandboxMode($storeId)) {
            $this->apiURL = 'https://secure.sandbox.tpay.com/api/gw/';
        }
    }

    public function createTransaction(array $data): array
    {
        return $this->create($data);
    }

    public function blikAlias(string $transactionId, string $blikAlias): array
    {
        return $this->blik($transactionId, '', $blikAlias);
    }
}
