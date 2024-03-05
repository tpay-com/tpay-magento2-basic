<?php

declare(strict_types=1);

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Laminas\Http\Request;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Service\TpayService;

class Redirect implements ActionInterface
{
    /** @var Session */
    protected $checkoutSession;

    /** @var TpayService */
    protected $tpayService;

    /** @var RedirectFactory */
    protected $redirectFactory;

    /** @var Request */
    protected $request;

    /** @var AdditionalPaymentInfoValidator */
    protected $additionalPaymentInfoValidator;

    public function __construct(
        TpayService $tpayService,
        Session $checkoutSession,
        RedirectFactory $redirectFactory,
        Request $request,
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
        $uid = $this->request->getQuery('uid');
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if (!$orderId || !$uid) {
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $additionalPaymentInfo = $this->tpayService->getPayment($orderId)->getData()['additional_information'];

        if ($this->additionalPaymentInfoValidator->validateCardData($additionalPaymentInfo)) {
            return $this->redirectFactory->create()->setPath('magento2basic/tpay/CardPayment');
        }

        if ($this->additionalPaymentInfoValidator->validatePresenceOfGroupOrChannel($additionalPaymentInfo) && $this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInfo)) {
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->redirectFactory->create()->setPath('magento2basic/tpay/Create');
    }
}
