<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model\Sales;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository as MagentoOrderRepository;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;

/**
 * Class OrderRepository
 *
 * @package tpaycom\magento2basic\Model\Sales
 */
class OrderRepository extends MagentoOrderRepository implements OrderRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getByIncrementId($incrementId)
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
