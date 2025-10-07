<?php

namespace Tpay\Magento2\Notification\Strategy\Factory;

use Magento\Framework\App\RequestInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorFactoryInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;

class NotificationProcessorFactory implements NotificationProcessorFactoryInterface
{
    /** @var list<NotificationProcessorInterface> */
    protected $strategies;

    /** @var RequestInterface */
    private $request;

    public function __construct(RequestInterface $request, array $strategies = [])
    {
        $this->strategies = $strategies;
        $this->request = $request;
    }

    public function create(array $data): NotificationProcessorInterface
    {
        if (null !== $this->request->getPost('card')) {
            return $this->strategies['card'];
        }

        if (null !== $this->request->getPost('event')) {
            return $this->strategies['blikAlias'];
        }

        return $this->strategies['default'];
    }
}
