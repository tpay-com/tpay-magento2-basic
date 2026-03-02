<?php

namespace Tpay\Magento2\Api\Notification\Strategy;

use Tpay\OpenApi\Model\Objects\Objects;

interface NotificationProcessorFactoryInterface
{
    /** @param array|Objects $notification */
    public function create($notification): NotificationProcessorInterface;
}
