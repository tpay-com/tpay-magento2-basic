<?php

declare(strict_types=1);

namespace Tpay\Magento2\Api\Sales;

use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;

interface OrderRepositoryInterface extends MagentoOrderRepositoryInterface
{
    /** Return new instance of Order by increment ID */
    public function getByIncrementId(string $incrementId): \Magento\Sales\Api\Data\OrderInterface;
}
