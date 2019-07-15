<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use tpaycom\magento2basic\Service\TpayService;
use Magento\Checkout\Model\Session;

/**
 * Class Redirect
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
class Redirect extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var TpayService
     */
    protected $tpayService;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param TpayService $tpayService
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        TpayService $tpayService,
        Session $checkoutSession
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
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
        if (
            (!isset($additionalPaymentInfo['group']) || (int)$additionalPaymentInfo['group'] < 1)
            && (!isset($additionalPaymentInfo['blik_code']) || strlen($additionalPaymentInfo['blik_code']) !== 6)
        ) {
            return $this->_redirect('checkout/cart');
        }
        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->_redirect('magento2basic/tpay/Create');
    }

}
