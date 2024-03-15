<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model\Sales;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository as MagentoOrderRepository;
use Tpay\Magento2\Api\Sales\OrderRepositoryInterface;

class OrderRepository extends MagentoOrderRepository implements OrderRepositoryInterface
{
    public function getByIncrementId(string $incrementId): OrderInterface
    {
        if (!$incrementId) {
            throw new InputException(__('Id required'));
        }

        /** @var OrderInterface $entity */
        $entity = $this->metadata->getNewInstance()->loadByIncrementId($incrementId);

        if (!$entity->getEntityId()) {
            throw new NoSuchEntityException(__('Requested entity doesn\'t exist'));
        }

        return $entity;
    }
}
