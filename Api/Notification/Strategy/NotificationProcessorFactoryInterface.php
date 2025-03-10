<?php

namespace Tpay\Magento2\Api\Notification\Strategy;

use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;

interface NotificationProcessorFactoryInterface
{
    public function create(array $data): NotificationProcessorInterface;
}
