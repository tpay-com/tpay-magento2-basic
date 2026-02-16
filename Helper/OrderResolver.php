<?php

namespace Tpay\Magento2\Helper;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order;

class OrderResolver
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var Order */
    private $orderResource;

    private $cache = [];

    public function __construct(OrderRepositoryInterface $orderRepository, Order $orderResource)
    {
        $this->orderRepository = $orderRepository;
        $this->orderResource = $orderResource;
    }

    public function getOrderByIncrementId(string $incrementId): OrderInterface
    {
        $orderId = $this->getOrderIdByIncrementId($incrementId);

        return $this->orderRepository->get($orderId);
    }

    public function getOrderIdByIncrementId(string $incrementId): int
    {
        if (!isset($this->cache[$incrementId])) {
            $connection = $this->orderResource->getConnection();
            $select = $connection->select()->from($this->orderResource->getMainTable(), ['entity_id'])->where('increment_id = ?', $incrementId);
            $orderId = $connection->fetchOne($select);
            $this->cache[$incrementId] = (int) $orderId;
        }

        return $this->cache[$incrementId];
    }
}
