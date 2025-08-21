<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Tpay\Magento2\Service\RedirectHandler;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Validator\AdditionalPaymentInfoValidator;

class Redirect implements ActionInterface
{
    /** @var Session */
    protected $checkoutSession;

    /** @var TpayService */
    protected $tpayService;

    /** @var RedirectHandler */
    protected $redirectFactory;

    /** @var RequestInterface */
    protected $request;

    /** @var AdditionalPaymentInfoValidator */
    protected $additionalPaymentInfoValidator;

    public function __construct(
        TpayService $tpayService,
        Session $checkoutSession,
        RedirectHandler $redirectFactory,
        RequestInterface $request,
        AdditionalPaymentInfoValidator $additionalPaymentInfoValidator
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->redirectFactory = $redirectFactory;
        $this->request = $request;
        $this->additionalPaymentInfoValidator = $additionalPaymentInfoValidator;
    }

    public function execute(): ResultInterface
    {
        $uid = $this->request->getParam('uid');
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if (!$orderId || !$uid) {
            return $this->redirectFactory->redirectCheckoutCart();
        }

        $additionalPaymentInfo = $this->tpayService->getPayment($orderId)->getData()['additional_information'];

        if ($this->additionalPaymentInfoValidator->validateCardData($additionalPaymentInfo)) {
            return $this->redirectFactory->redirectCardPayment();
        }

        if ($this->additionalPaymentInfoValidator->validatePresenceOfGroupOrChannel($additionalPaymentInfo) && $this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInfo)) {
            return $this->redirectFactory->redirectCheckoutCart();
        }

        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->redirectFactory->redirectCreate();
    }
}
