<?php

namespace Tpay\Magento2\Notification\Strategy\Factory;

use Tpay\Magento2\Notification\Strategy\NotificationProcessorInterface;

interface NotificationProcessorFactoryInterface
{
    public function create(array $data): NotificationProcessorInterface;
}
