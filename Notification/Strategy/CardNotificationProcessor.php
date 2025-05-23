<?php

namespace Tpay\Magento2\Notification\Strategy;

use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OriginApi\Webhook\JWSVerifiedPaymentNotification;

class CardNotificationProcessor implements NotificationProcessorInterface
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

        $orderId = base64_decode($notification['order_id']);
        $order = $this->tpayService->getOrderById($orderId);

        if ('correct' === $notification['status']) {
            $this->tpayService->confirmPayment($order, $notification['amount'], $notification['sale_auth'], []);
            $this->saveOriginCard($notification, $orderId);
        }
    }

    private function saveOriginCard(array $notification, string $orderId)
    {
        $order = $this->tpayService->getOrderById($orderId);

        $payment = $this->tpayService->getPayment($orderId);
        $additionalPaymentInformation = $payment->getData()['additional_information'];

        if (isset($notification['cli_auth']) && $this->tpayConfig->getCardSaveEnabled() && !$this->tpay->isCustomerGuest($orderId)) {
            $this->tokensService->setCustomerToken(
                (string) $order->getCustomerId(),
                $notification['cli_auth'],
                $notification['card'],
                $additionalPaymentInformation['card_vendor']
            );
        }
    }
}
