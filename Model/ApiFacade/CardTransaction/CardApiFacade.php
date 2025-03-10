<?php

namespace Tpay\Magento2\Model\ApiFacade\CardTransaction;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;

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
        $this->useOpenCard = 'PLN' === $storeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE);
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
