<?php

namespace Tpay\Magento2\Notification\Strategy;

use Exception;
use Magento\Sales\Model\Order;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification;

class DefaultNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayConfigInterface */
    protected $tpayConfig;

    /** @var TpayService */
    protected $tpayService;

    /** @var TpayTokensService */
    protected $tokensService;

    /** @var TpayInterface */
    protected $tpay;

    public function __construct(
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        TpayTokensService $tokensService,
        TpayInterface $tpayModel
    ) {
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->tpay = $tpayModel;
    }

    public function process(?int $storeId)
    {
        $notification = (new JWSVerifiedPaymentNotification(
            $this->tpayConfig->getSecurityCode($storeId),
            !$this->tpayConfig->useSandboxMode($storeId)
        ))->getNotification();

        $notification = $notification->getNotificationAssociative();
        $orderId = base64_decode($notification['tr_crc']);

        if ('PAID' === $notification['tr_status']) {
            return $this->getPaidTransactionResponse($orderId);
        }

        $this->saveCard($notification, $orderId);
        $this->tpayService->setOrderStatus($orderId, $notification, $this->tpayConfig);
    }

    protected function getPaidTransactionResponse(string $orderId): string
    {
        $order = $this->tpayService->getOrderById($orderId);

        if (!$order->getId()) {
            throw new Exception(sprintf('Unable to get order by orderId %s', $orderId));
        }

        if (Order::STATE_CANCELED === $order->getState()) {
            return 'FALSE';
        }

        return 'TRUE';
    }

    private function saveCard(array $notification, string $orderId)
    {
        $order = $this->tpayService->getOrderById($orderId);

        if (isset($notification['card_token']) && !$this->tpay->isCustomerGuest($orderId)) {
            $token = $this->tokensService->getWithoutAuthCustomerTokens(
                (string) $order->getCustomerId(),
                $notification['tr_crc']
            );

            if (!empty($token)) {
                $this->tokensService->updateTokenById((int) $token['tokenId'], $notification['card_token']);
            }
        }
    }
}
