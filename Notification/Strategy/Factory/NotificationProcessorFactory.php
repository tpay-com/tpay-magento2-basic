<?php

namespace Tpay\Magento2\Notification\Strategy\Factory;

use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasRegister;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasUnregister;

class NotificationProcessorFactory implements NotificationProcessorFactoryInterface
{
    /** @var list<NotificationProcessorInterface> */
    protected $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
    }

    public function create($notification): NotificationProcessorInterface
    {
        if ($notification instanceof BlikAliasRegister || $notification instanceof BlikAliasUnregister) {
            return $this->strategies['blikAlias'];
        }

        if (is_array($notification) && isset($data['card'])) {
            return $this->strategies['card'];
        }

        return $this->strategies['default'];
    }
}
