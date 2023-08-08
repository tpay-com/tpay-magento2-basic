<?php

namespace tpaycom\magento2basic\Api\Sales;

use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;

/**
 * Interface OrderRepositoryInterface
 */
interface OrderRepositoryInterface extends MagentoOrderRepositoryInterface
{
    /**
     * Return new instance of Order by increment ID
     *
     * @param string $incrementId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getByIncrementId($incrementId);
}
