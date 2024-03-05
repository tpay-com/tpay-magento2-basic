<?php

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\CardTransaction\CardApiFacade;
use tpaycom\magento2basic\Service\TpayService;

class CardPayment implements ActionInterface
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var CardApiFacade */
    private $cardApiFacade;

    /** @var RedirectFactory */
    private $redirectFactory;
    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var TpayTokensService */
    private $tokensService;

    public function __construct(
        TpayService $tpayService,
        Session $checkoutSession,
        CardApiFacade $cardApiFacade,
        RedirectFactory $redirectFactory
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->cardApiFacade = $cardApiFacade;
        $this->redirectFactory = $redirectFactory;
        Util::$loggingEnabled = false;
    }

    public function execute()
    {
        /** @var int $orderId */
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $additionalPaymentInformation = $payment->getData()['additional_information'];

            if (!$additionalPaymentInformation[TpayInterface::TERMS_ACCEPT]) {
                return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
            }

            $redirectUrl = $this->cardApiFacade->makeCardTransaction($orderId);

            return $this->redirectFactory->create()->setPath($redirectUrl);
        }

        $this->checkoutSession->unsQuoteId();

        return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
    }
}
