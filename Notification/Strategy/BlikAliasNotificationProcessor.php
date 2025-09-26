<?php

declare(strict_types=1);

namespace Tpay\Magento2\Notification\Strategy;

use Magento\Framework\App\RequestInterface;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Service\TpayAliasServiceInterface;

class BlikAliasNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayAliasServiceInterface */
    protected $aliasService;

    /** @var RequestInterface */
    private $request;

    public function __construct(TpayAliasServiceInterface $aliasService, RequestInterface $request)
    {
        $this->aliasService = $aliasService;
        $this->request = $request;
    }

    public function process(?int $storeId = null)
    {
        $response = $this->request->getPost()->toArray();
        $userId = (int) explode('-', $response['msg_value']['value'])[1];

        if ('ALIAS_REGISTER' === $response['event']) {
            $this->aliasService->saveCustomerAlias($userId, $response['msg_value']['value']);
        }

        if ('ALIAS_UNREGISTER' === $response['event']) {
            $this->aliasService->removeCustomerAlias($userId, $response['msg_value']['value']);
        }
    }
}
