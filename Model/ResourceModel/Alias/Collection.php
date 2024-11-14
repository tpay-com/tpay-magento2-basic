<?php

namespace Tpay\Magento2\Model\ResourceModel\Alias;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Tpay\Magento2\Model\Alias;
use Tpay\Magento2\Model\ResourceModel\Alias as AliasResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct() // phpcs:ignore
    {
        $this->_init(Alias::class, AliasResourceModel::class);
    }
}
