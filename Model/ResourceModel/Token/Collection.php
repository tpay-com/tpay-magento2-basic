<?php

namespace TpayCom\Magento2Basic\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TpayCom\Magento2Basic\Model\ResourceModel\Token as TokenResourceModel;
use TpayCom\Magento2Basic\Model\Token;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Token::class, TokenResourceModel::class);
    }
}
