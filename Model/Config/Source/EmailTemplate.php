<?php

namespace Tpay\Magento2\Model\Config\Source;

class EmailTemplate extends \Magento\Config\Model\Config\Source\Email\Template
{
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
