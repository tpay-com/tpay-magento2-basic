<?php

namespace Tpay\Magento2\Notification\Strategy;

use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotificationFactory;

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

    private JWSVerifiedPaymentNotificationFactory $notificationFactory;

    public function __construct(
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        TpayTokensService $tokensService,
        TpayInterface $tpayModel,
        JWSVerifiedPaymentNotificationFactory $notificationFactory
    ) {
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->tpay = $tpayModel;
        $this->notificationFactory = $notificationFactory;
    }

    public function process(?int $storeId)
    {
        $notification = $this->notificationFactory->create(['merchantSecret' => $this->tpayConfig->getSecurityCode($storeId), 'productionMode' => !$this->tpayConfig->useSandboxMode($storeId)])->getNotification();

        $notification = $notification->getNotificationAssociative();
        $orderId = base64_decode($notification['tr_crc']);

        $order = $this->tpayService->getOrderById($orderId);

        if ('TRUE' === $notification['tr_status']) {
            $this->tpayService->confirmPayment($order, $notification['tr_amount'], $notification['tr_id'], []);
            $this->saveCard($notification, $orderId);
        }
        if ('CHARGEBACK' === $notification['tr_status']) {
            $this->tpayService->addCommentToHistory($orderId, __('Transaction has been refunded via Tpay Transaction Panel'));
        }
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
