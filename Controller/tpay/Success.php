<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;

/**
 * Class Success
 */
class Success extends Action
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->messageManager->addSuccessMessage(__('Thank you for your payment!'));

        return $this->_redirect('checkout/onepage/success');
    }
}
