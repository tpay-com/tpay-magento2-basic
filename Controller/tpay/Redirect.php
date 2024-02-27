<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Laminas\Http\Request;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Service\TpayService;

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

    public function __construct(
        TpayService $tpayService,
        Session $checkoutSession,
        RedirectFactory $redirectFactory,
        Request $request
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->redirectFactory = $redirectFactory;
        $this->request = $request;
    }

    public function execute(): ResultInterface
    {
        $uid = $this->request->getQuery('uid');
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if (!$orderId || !$uid) {
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $payment = $this->tpayService->getPayment($orderId);
        $paymentData = $payment->getData();
        $additionalPaymentInfo = $paymentData['additional_information'];

        if (!empty($additionalPaymentInfo[TpayInterface::CARDDATA]) || !empty($additionalPaymentInfo[TpayInterface::CARD_ID])) {
            return $this->redirectFactory->create()->setPath('magento2basic/tpay/CardPayment');
        }

        if (empty(
            array_intersect(array_keys($additionalPaymentInfo), [TpayInterface::GROUP, TpayInterface::CHANNEL])
            ) && (!array_key_exists(TpayInterface::BLIK_CODE, $additionalPaymentInfo) || 6 !== strlen(
                    $additionalPaymentInfo[TpayInterface::BLIK_CODE]
                ))) {
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->redirectFactory->create()->setPath('magento2basic/tpay/Create');
    }
}
