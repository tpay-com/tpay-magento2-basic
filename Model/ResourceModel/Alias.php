<?php

namespace Tpay\Magento2\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Alias extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('tpay_blik_aliases', 'id');
    }
}
