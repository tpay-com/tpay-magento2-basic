<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Magento\Framework\App\ResourceConnection;
use Tpay\Magento2\Model\Api\AliasRepositoryInterface;
use Tpay\Magento2\Model\ResourceModel\Alias\Collection;

class TpayAliasService implements TpayAliasServiceInterface
{
    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var Collection */
    protected $collection;

    /** @var AliasRepositoryInterface */
    protected $aliasRepository;

    public function __construct(
        ResourceConnection $resourceConnection,
        Collection $collection,
        AliasRepositoryInterface $aliasRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->collection = $collection;
        $this->aliasRepository = $aliasRepository;
    }

    public function getCustomerAlias(int $customerId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('tpay_blik_aliases');

        $select = $connection->select()
            ->from($tableName)
            ->where('cli_id = ?', $customerId);

        return $connection->fetchRow($select);
    }

    public function saveCustomerAlias(int $customerId, string $alias): void
    {
        $aliasEntity = $this->aliasRepository->findByCustomerId($customerId);

        if (!$aliasEntity->getId()) {
            $aliasEntity->setCustomerId($customerId)
                ->setAlias($alias)
                ->created();

            $this->aliasRepository->save($aliasEntity);
        }
    }

    /**
     * @inheritDoc
     */
    public function removeCustomerAlias(int $customerId, string $alias): void
    {
        $aliasEntity = $this->aliasRepository->findByCustomerId($customerId);

        if (!$aliasEntity->getId()) {
            throw new \Exception("Alias for customerId {$customerId} not found");
        }

        $this->aliasRepository->remove($aliasEntity);
    }
}
