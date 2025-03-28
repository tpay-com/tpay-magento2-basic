<?php

namespace Tpay\Magento2\Model\ApiFacade\CardTransaction;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CardApiFacade
{
    /** @var CardOrigin */
    private $cardOrigin;

    /** @var CardOpen */
    private $cardOpen;

    /** @var bool */
    private $useOpenCard;

    public function __construct(CardOrigin $cardOrigin, CardOpen $cardOpen, ScopeConfigInterface $storeConfig)
    {
        $this->cardOrigin = $cardOrigin;
        $this->cardOpen = $cardOpen;
        $this->useOpenCard
            = 'PLN' === $storeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE)
            && $storeConfig->isSetFlag('payment/tpaycom_magento2basic/openapi_settings/open_api_active', ScopeInterface::SCOPE_STORE);
    }

    public function makeCardTransaction(string $orderId, ?array $customerToken = null): string
    {
        return $this->getCurrent()->makeFullCardTransactionProcess($orderId, $customerToken);
    }

    public function payTransaction(string $orderId, array $additionalPaymentInformation, ?string $transactionId = null, ?array $customerToken = null): string
    {
        return $this->isOpenApiUse() ? $this->cardOpen->payTransaction($orderId, $additionalPaymentInformation, $transactionId, $customerToken) : 'error';
    }

    public function isOpenApiUse(): bool
    {
        return (bool) $this->useOpenCard;
    }

    private function getCurrent()
    {
        return $this->useOpenCard ? $this->cardOpen : $this->cardOrigin;
    }
}
