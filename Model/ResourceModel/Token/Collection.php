<?php

namespace tpaycom\magento2basic\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('tpaycom\magento2basic\Model\Tokens', 'tpaycom\magento2basic\Model\ResourceModel\Token');
    }
}
