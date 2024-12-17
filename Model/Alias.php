<?php

namespace Tpay\Magento2\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Tpay\Magento2\Model\Api\Data\AliasInterface;

class Alias extends AbstractModel implements AliasInterface
{
    public function __construct(
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_init(ResourceModel\Alias::class);
    }

    public function setCustomerId(int $id): AliasInterface
    {
        $this->setData('cli_id', $id);

        return $this;
    }

    public function setAlias(string $alias): AliasInterface
    {
        $this->setData('alias', $alias);

        return $this;
    }

    public function created(): AliasInterface
    {
        $this->setData('created_at', date('Y-m-d H:i:s'));

        return $this;
    }
}
