<?php

namespace Tpay\Magento2\Notification\Strategy;

use RuntimeException;
use Tpay\Magento2\Api\Notification\Strategy\NotificationProcessorInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OpenApi\Model\Objects\NotificationBody\BasicPayment;

class DefaultNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayService */
    protected $tpayService;

    /** @var TpayTokensService */
    protected $tokensService;

    /** @var TpayInterface */
    protected $tpay;

    public function __construct(
        TpayService $tpayService,
        TpayTokensService $tokensService,
        TpayInterface $tpay
    ) {
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->tpay = $tpay;
    }

    public function process($notification, ?int $storeId = null)
    {
        if (!$notification instanceof BasicPayment) {
            throw new RuntimeException('Invalid payment notification type');
        }

        $orderId = base64_decode($notification->tr_crc->getValue());
        $order = $this->tpayService->getOrderById($orderId);

        switch ($notification->tr_status->getValue()) {
            case 'TRUE':
            case 'PAID':
                $this->tpayService->confirmPayment(
                    $order,
                    $notification->tr_amount->getValue(),
                    $notification->tr_id->getValue(),
                    []
                );
                break;
            case 'CHARGEBACK':
                $this->tpayService->addCommentToHistory(
                    $orderId,
                    __('Transaction has been refunded via Tpay Transaction Panel')
                );
                break;
        }

        $this->saveCard($notification, $orderId);
    }

    private function saveCard(BasicPayment $notification, string $orderId)
    {
        if (!$notification->card_token) {
            return;
        }

        if ($this->tpay->isCustomerGuest($orderId)) {
            return;
        }

        $order = $this->tpayService->getOrderById($orderId);

        $token = $this->tokensService->getWithoutAuthCustomerTokens(
            (string) $order->getCustomerId(),
            $notification->tr_crc->getValue()
        );

        if (!empty($token)) {
            $this->tokensService->updateTokenById((int) $token['tokenId'], $notification->card_token->getValue());
        }
    }
}
