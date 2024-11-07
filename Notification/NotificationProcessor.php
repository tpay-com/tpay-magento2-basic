<?php

namespace Tpay\Magento2\Notification;

use Tpay\Magento2\Notification\Strategy\Factory\NotificationProcessorFactoryInterface;

class NotificationProcessor
{
    /** @var NotificationProcessorFactoryInterface */
    protected $factory;

    public function __construct(NotificationProcessorFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function process()
    {
        $strategy = $this->factory->create();

        return $strategy->process();
    }
}
