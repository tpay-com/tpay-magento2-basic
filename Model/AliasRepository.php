<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Tpay\Magento2\Api\AliasRepositoryInterface;
use Tpay\Magento2\Api\Data\AliasInterface;
use Tpay\Magento2\Model\ResourceModel\Alias as AliasResourceModel;

class AliasRepository implements AliasRepositoryInterface
{
    /** @var AliasFactory */
    protected $aliasFactory;

    /** @var AliasResourceModel */
    protected $aliasResourceModel;

    public function __construct(AliasFactory $aliasFactory, AliasResourceModel $aliasResourceModel)
    {
        $this->aliasFactory = $aliasFactory;
        $this->aliasResourceModel = $aliasResourceModel;
    }

    public function findByCustomerId(int $customerId): ?AliasInterface
    {
        $alias = $this->aliasFactory->create();
        $this->aliasResourceModel->load($alias, $customerId, 'cli_id');

        return $alias;
    }

    public function save(AliasInterface $alias): void
    {
        $this->aliasResourceModel->save($alias);
    }

    public function remove(AliasInterface $alias): void
    {
        $this->aliasResourceModel->delete($alias);
    }
}
