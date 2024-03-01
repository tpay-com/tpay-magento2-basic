<?php

declare(strict_types=1);

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Service\TpayService;

class Redirect extends Action
{
    /** @var Session */
    protected $checkoutSession;

    /** @var TpayService */
    protected $tpayService;

    public function __construct(Context $context, TpayService $tpayService, Session $checkoutSession)
    {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    public function execute()
    {
        $uid = $this->getRequest()->getParam('uid');
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if (!$orderId || !$uid) {
            return $this->_redirect('checkout/cart');
        }

        $payment = $this->tpayService->getPayment($orderId);
        $paymentData = $payment->getData();
        $additionalPaymentInfo = $paymentData['additional_information'];

        if (!empty($additionalPaymentInfo[TpayInterface::CARDDATA]) || !empty($additionalPaymentInfo[TpayInterface::CARD_ID])) {
            return $this->_redirect('magento2basic/tpay/CardPayment');
        }

        if (empty(array_intersect(array_keys($additionalPaymentInfo), [TpayInterface::GROUP, TpayInterface::CHANNEL])) && (!array_key_exists(TpayInterface::BLIK_CODE, $additionalPaymentInfo) || 6 !== strlen($additionalPaymentInfo[TpayInterface::BLIK_CODE]))) {
            return $this->_redirect('checkout/cart');
        }
        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->_redirect('magento2basic/tpay/Create');
    }
}
