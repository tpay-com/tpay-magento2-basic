<?php

namespace tpaycom\magento2basic\Model\ApiFacade\CardTransaction;

use Exception;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Service\TpayTokensService;

class CardApiFacade
{
    /** @var CardOrigin */
    private $cardOrigin;

    /** @var CardOpen */
    private $cardOpen;

    /** @var bool */
    private $useOpenCard;

    public function __construct(TpayInterface $tpay, TpayTokensService $tokensService, TpayService $tpayService)
    {
        $this->cardOrigin = new CardOrigin($tpay, $tokensService, $tpayService);
        $this->createOpenApiInstance($tpay, $tokensService, $tpayService);
    }

    public function makeCardTransaction(string $orderId): string
    {
        return $this->getCurrent()->makeCardTransaction($orderId);
    }

    private function getCurrent()
    {
        return $this->useOpenCard ? $this->cardOpen : $this->cardOrigin;
    }

    private function createOpenApiInstance(TpayInterface $tpay, TpayTokensService $tokensService, TpayService $tpayService)
    {
        try {
            $this->cardOpen = new CardOpen($tpay, $tokensService, $tpayService);
            $this->useOpenCard = true;
        } catch (Exception $exception) {
            $this->cardOpen = null;
            $this->useOpenCard = false;
        }
    }
}
