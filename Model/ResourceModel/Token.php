<?php

namespace Tpay\Magento2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Token extends AbstractDb
{
    protected function _construct()// phpcs:ignore
    {
        $this->_init('tpay_credit_cards', 'id');
    }
}
