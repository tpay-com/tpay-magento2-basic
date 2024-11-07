<?php

namespace Tpay\Magento2\Notification\Strategy\Factory;

use Tpay\Magento2\Notification\Strategy\NotificationProcessorInterface;

class NotificationProcessorFactory implements NotificationProcessorFactoryInterface
{
    /** @var NotificationProcessorInterface[] */
    protected $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
    }

    public function create(array $data): NotificationProcessorInterface
    {
        if (isset($_POST['card'])) {
            return $this->strategies['card'];
        }

        if (isset($_POST['event'])) {
            return $this->strategies['blikAlias'];
        }

        return $this->strategies['default'];
    }
}
