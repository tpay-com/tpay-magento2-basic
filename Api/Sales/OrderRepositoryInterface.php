<?php
/**
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Api\Sales;

use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;

/**
 * Interface OrderRepositoryInterface
 *
 * @package tpaycom\magento2basic\Api\Sales
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
