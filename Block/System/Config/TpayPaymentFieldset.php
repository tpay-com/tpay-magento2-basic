<?php

namespace Tpay\Magento2\Block\System\Config;

use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Fieldset;

class TpayPaymentFieldset extends Fieldset
{
    protected function _getHeaderCommentHtml($element)
    {
        $block = $this->getLayout()->createBlock(Template::class)->setTemplate('Tpay_Magento2::system/config/header.phtml');
        $registrationUrl = $this->_scopeConfig->getValue('payment/tpaycom_magento2basic/registration_override');
        if (empty($registrationUrl)) {
            $registrationUrl = 'https://register.tpay.com/';
        }
        $block->setData('registration_url', $registrationUrl);

        return $block->toHtml();
    }
}
