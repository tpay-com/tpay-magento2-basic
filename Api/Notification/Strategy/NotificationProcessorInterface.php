<?php

namespace Tpay\Magento2\Api\Notification\Strategy;

interface NotificationProcessorInterface
{
    public function process(?int $storeId);
}
