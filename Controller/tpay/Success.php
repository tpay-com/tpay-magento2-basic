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

/**
 * Class Success
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
class Success extends Action
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->messageManager->addSuccessMessage(__('Dziękujemy za dokonanie płatności.'));

        return $this->_redirect('checkout/cart');
    }
}
