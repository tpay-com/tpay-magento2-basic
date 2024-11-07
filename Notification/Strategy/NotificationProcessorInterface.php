<?php

namespace Tpay\Magento2\Notification\Strategy;

interface NotificationProcessorInterface
{
    public function process(?int $storeId);
}
