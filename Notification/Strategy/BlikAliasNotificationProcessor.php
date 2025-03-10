<?php

declare(strict_types=1);

namespace Tpay\Magento2\Notification\Strategy;

use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Api\TpayAliasServiceInterface;

class BlikAliasNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayAliasServiceInterface */
    protected $aliasService;

    public function __construct(TpayAliasServiceInterface $aliasService)
    {
        $this->aliasService = $aliasService;
    }

    public function process(?int $storeId = null)
    {
        $response = $_POST;
        $userId = (int) explode('-', $response['msg_value']['value'])[1];

        if ('ALIAS_REGISTER' === $response['event']) {
            $this->aliasService->saveCustomerAlias($userId, $response['msg_value']['value']);
        }

        if ('ALIAS_UNREGISTER' === $response['event']) {
            $this->aliasService->removeCustomerAlias($userId, $response['msg_value']['value']);
        }
    }
}
