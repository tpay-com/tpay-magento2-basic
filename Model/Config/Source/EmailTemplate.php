<?php

namespace Tpay\Magento2\Model\Config\Source;

class EmailTemplate extends \Magento\Config\Model\Config\Source\Email\Template
{
    /**
     * This method is overrided because "merchant location" selector dynamically
     * changes first part of path (eg. payment_us, payment_other) based on
     * selected country - making internals of base logic Email Template Source
     * fail to find default email template based on exact match of configuration
     * option path (as original value starts with payment)
     *
     * @return string
     */
    public function getPath()
    {
        $path = parent::getPath();
        $path = explode('/', $path);
        if ('payment' !== $path[0]) {
            $path[0] = 'payment';
        }

        return implode('/', $path);
    }
}
