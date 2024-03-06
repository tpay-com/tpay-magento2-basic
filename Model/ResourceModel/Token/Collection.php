<?php

namespace Tpay\Magento2\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Tpay\Magento2\Model\ResourceModel\Token as TokenResourceModel;
use Tpay\Magento2\Model\Token;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Token::class, TokenResourceModel::class);
    }
}
