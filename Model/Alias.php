<?php

namespace Tpay\Magento2\Model;

use Magento\Framework\Model\AbstractModel;

class Alias extends AbstractModel
{
    public function __construct()
    {
        $this->_init(ResourceModel\Alias::class);
    }
}
