<?php

namespace Tpay\Magento2\Api\Notification\Strategy;

interface NotificationProcessorFactoryInterface
{
    public function create(array $data): NotificationProcessorInterface;
}
