<?php

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\CardTransaction\CardApiFacade;
use Tpay\Magento2\Service\RedirectHandler;
use Tpay\Magento2\Service\TpayService;

class CardPayment implements ActionInterface
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var CardApiFacade */
    private $cardApiFacade;

    /** @var RedirectHandler */
    private $redirectFactory;

    public function __construct(
        TpayService $tpayService,
        Session $checkoutSession,
        CardApiFacade $cardApiFacade,
        RedirectHandler $redirectFactory
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->cardApiFacade = $cardApiFacade;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        /** @var int $orderId */
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $additionalPaymentInformation = $payment->getData()['additional_information'];

            if (!$additionalPaymentInformation[TpayInterface::TERMS_ACCEPT]) {
                return $this->redirectFactory->redirectError();
            }

            $redirectUrl = $this->cardApiFacade->makeCardTransaction($orderId);

            return $this->redirectFactory->redirectTransaction($redirectUrl);
        }

        $this->checkoutSession->unsQuoteId();

        return $this->redirectFactory->redirectError();
    }
}
