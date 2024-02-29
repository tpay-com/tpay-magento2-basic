<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayConfigInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\CardTransaction\CardApiFacade;
use tpaycom\magento2basic\Model\TpayPayment;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Service\TpayTokensService;

class CardPayment extends Action
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(Context $context, TpayInterface $tpayModel, TpayConfigInterface $tpayConfig, TpayService $tpayService, Session $checkoutSession, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->tokensService = $tokensService;
        $this->storeManager = $storeManager;
        Util::$loggingEnabled = false;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var int $orderId */
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $additionalPaymentInformation = $payment->getData()['additional_information'];

            if (!$additionalPaymentInformation[TpayPayment::TERMS_ACCEPT]) {
                return $this->_redirect('magento2basic/tpay/error');
            }

            $cardTransaction = new CardApiFacade($this->tpay, $this->tpayConfig, $this->tokensService, $this->tpayService, $this->storeManager);
            $redirectUrl = $cardTransaction->makeCardTransaction($orderId);

            return $this->_redirect($redirectUrl);
        }
        $this->checkoutSession->unsQuoteId();

        return $this->_redirect('magento2basic/tpay/error');
    }
}
