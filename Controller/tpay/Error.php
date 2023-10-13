<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;

class Error extends Action
{
    public function execute()
    {
        $this->messageManager->addWarningMessage(__('There was an error during your payment.'));

        return $this->_redirect('checkout/onepage/failure');
    }
}
