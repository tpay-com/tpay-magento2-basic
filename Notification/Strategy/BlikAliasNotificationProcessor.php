<?php

declare(strict_types=1);

namespace Tpay\Magento2\Notification\Strategy;

use Tpay\Magento2\Service\TpayAliasServiceInterface;

class BlikAliasNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayAliasServiceInterface */
    protected $aliasService;

    public function __construct(TpayAliasServiceInterface $aliasService)
    {
        $this->aliasService = $aliasService;
    }

    public function process(?int $storeId)
    {
        $response = $_POST;
        $userId = (int) explode('_', $response['value'])[1];

        if ($response['event'] === 'ALIAS_REGISTER') {
            $this->aliasService->saveCustomerAlias($userId, $response['value']);
        }

        if ($response['event'] === 'ALIAS_UNREGISTER') {
            $this->aliasService->removeCustomerAlias($userId, $response['value']);
        }
    }
}
