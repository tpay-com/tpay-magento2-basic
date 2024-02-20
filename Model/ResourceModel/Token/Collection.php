<?php

namespace tpaycom\magento2basic\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use tpaycom\magento2basic\Model\ResourceModel\Token as TokenResourceModel;
use tpaycom\magento2basic\Model\Token;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Token::class, TokenResourceModel::class);
    }
}
