<?php

namespace Tpay\Magento2\Model\ApiFacade\CardTransaction;

use Exception;
use Magento\Store\Model\StoreManagerInterface;
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

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var TpayService */
    private $tpayService;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var bool */
    private $useOpenCard;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService, StoreManagerInterface $storeManager)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->tokensService = $tokensService;
        $this->tpayService = $tpayService;
        $this->storeManager = $storeManager;
    }

    public function makeCardTransaction(string $orderId, ?array $customerToken = null): string
    {
        return $this->getCurrent()->makeFullCardTransactionProcess($orderId, $customerToken);
    }

    public function payTransaction(string $orderId, array $additionalPaymentInformation, ?string $transactionId = null, ?array $customerToken = null): string
    {
        $this->connectApi();

        return $this->useOpenCard ? $this->cardOpen->payTransaction($orderId, $additionalPaymentInformation, $transactionId, $customerToken) : 'error';
    }

    public function isOpenApiUse(): bool
    {
        $this->connectApi();

        return (bool) $this->useOpenCard;
    }

    private function getCurrent()
    {
        $this->connectApi();

        return $this->useOpenCard ? $this->cardOpen : $this->cardOrigin;
    }

    private function connectApi()
    {
        if (null == $this->cardOpen && null === $this->cardOrigin) {
            $this->createCardOriginApiInstance($this->tpay, $this->tpayConfig, $this->tokensService, $this->tpayService);
            $this->createOpenApiInstance($this->tpay, $this->tpayConfig, $this->tokensService, $this->tpayService);
        }
    }

    private function createCardOriginApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService)
    {
        if (!$tpayConfig->isCardEnabled()) {
            $this->cardOrigin = null;

            return;
        }

        try {
            $this->cardOrigin = new CardOrigin($tpay, $tpayConfig, $tokensService, $tpayService);
        } catch (Exception $exception) {
            $this->cardOrigin = null;
        }
    }

    private function createOpenApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService)
    {
        if ('PLN' !== $this->storeManager->getStore()->getBaseCurrencyCode() || !$tpayConfig->isCardEnabled()) {
            $this->cardOpen = null;
            $this->useOpenCard = false;

            return;
        }

        try {
            $this->cardOpen = new CardOpen($tpay, $tpayConfig, $tokensService, $tpayService);
            $this->useOpenCard = true;
        } catch (Exception $exception) {
            $this->cardOpen = null;
            $this->useOpenCard = false;
        }
    }
}
