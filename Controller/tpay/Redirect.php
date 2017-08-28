<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Block\Payment\tpay\Redirect as RedirectBlock;
use tpaycom\magento2basic\Model\Transaction;
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
     * @var TpayInterface
     */
    private $tpay;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param TpayInterface $tpayModel
     * @param TpayService $tpayService
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TpayService $tpayService,
        Session $checkoutSession
    ) {
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->tpay = $tpayModel;

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
        $this->tpayService->setOrderStatePendingPayment($orderId, true);

        return $this->_redirect('magento2basic/tpay/Blik');

    }

}
