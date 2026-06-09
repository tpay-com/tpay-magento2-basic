<?php

namespace Tpay\Magento2\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Tpay\Magento2\Model\TpayPayment;

class InstallOrderStatus implements DataPatchInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('sales_order_status'),
            [
                'status' => TpayPayment::ORDER_STATUS_PENDING,
                'label' => __('Pending Payment with Tpay'),
            ]
        );

        $this->moduleDataSetup->getConnection()->insert(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            [
                'status' => TpayPayment::ORDER_STATUS_PENDING,
                'state' => 'pending_payment',
                'is_default' => 0,
                'visible_on_front' => 1,
            ]
        );

        return $this;
    }
}
