<?php

namespace TpayCom\Magento2Basic\Model\ApiFacade\CardTransaction;

use Exception;
use Magento\Store\Model\StoreManagerInterface;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Service\TpayService;
use TpayCom\Magento2Basic\Service\TpayTokensService;

class CardApiFacade
{
    /** @var CardOrigin */
    private $cardOrigin;

    /** @var CardOpen */
    private $cardOpen;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var bool */
    private $useOpenCard;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        $this->createCardOriginApiInstance($tpay, $tpayConfig, $tokensService, $tpayService);
        $this->createOpenApiInstance($tpay, $tpayConfig, $tokensService, $tpayService);
    }

    public function makeCardTransaction(string $orderId): string
    {
        return $this->getCurrent()->makeCardTransaction($orderId);
    }

    private function getCurrent()
    {
        return $this->useOpenCard ? $this->cardOpen : $this->cardOrigin;
    }

    private function createCardOriginApiInstance(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService)
    {
        if (!$tpayConfig->isOriginApiEnabled()) {
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
        if ('PLN' !== $this->storeManager->getStore()->getCurrentCurrencyCode() || !$tpayConfig->isOpenApiEnabled()) {
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
