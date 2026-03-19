<?php

declare(strict_types=1);

namespace Tpay\Magento2\Notification\Strategy;

use RuntimeException;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Service\TpayAliasServiceInterface;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasRegister;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasUnregister;

class BlikAliasNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayAliasServiceInterface */
    protected $aliasService;

    public function __construct(TpayAliasServiceInterface $aliasService)
    {
        $this->aliasService = $aliasService;
    }

    public function process($notification)
    {
        if ($notification instanceof BlikAliasRegister) {
            $alias = (string) $notification->value->getValue();
            $userId = (int) explode('-', $alias)[1];

            $this->aliasService->saveCustomerAlias($userId, $alias);

            return;
        }

        if ($notification instanceof BlikAliasUnregister) {
            $alias = (string) $notification->value->getValue();
            $userId = (int) explode('-', $alias)[1];

            $this->aliasService->removeCustomerAlias($userId, $alias);

            return;
        }

        throw new RuntimeException('Unsupported BLIK notification type');
    }
}
