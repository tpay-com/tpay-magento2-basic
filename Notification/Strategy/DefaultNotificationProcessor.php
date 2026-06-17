<?php

namespace Tpay\Magento2\Notification\Strategy;

use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        TpayService $tpayService,
        TpayTokensService $tokensService,
        TpayInterface $tpay,
        LoggerInterface $logger
    ) {
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->tpay = $tpay;
        $this->logger = $logger;
    }

    public function process($notification)
    {
        if (!$notification instanceof BasicPayment) {
            throw new RuntimeException('Invalid payment notification type');
        }

        if ($notification->isTestNotification()) {
            $this->logger->info('Received test notification: '.print_r($notification->getNotificationAssociative(), true));

            return;
        }

        $orderId = base64_decode($notification->tr_crc->getValue());
        $order = $this->tpayService->getOrderById($orderId);

        if (!$this->validateCurrency($order, $notification)) {
            $this->logger->error(sprintf(
                'Currency mismatch for order %s: order=%s, notification=%s',
                $order->getIncrementId(),
                $order->getBaseCurrencyCode(),
                $notification->tr_currency ? $notification->tr_currency->getValue() : 'null'
            ));

            throw new RuntimeException('Order currency mismatch');
        }

        if (!$this->validateAmount($order, $notification)) {
            $this->logger->error(sprintf(
                'Amount mismatch for order %s: order=%s, notification=%s',
                $order->getIncrementId(),
                $order->getGrandTotal(),
                $notification->tr_amount->getValue()
            ));

            throw new RuntimeException('Order amount mismatch');
        }

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

    private function validateAmount($order, BasicPayment $notification): bool
    {
        $orderAmount = number_format((float) $order->getGrandTotal(), 2, '.', '');
        $notificationAmount = number_format((float) $notification->tr_amount->getValue(), 2, '.', '');

        return $orderAmount === $notificationAmount;
    }

    private function validateCurrency($order, BasicPayment $notification): bool
    {
        $value = null;

        if (isset($notification->tr_currency) && $notification->tr_currency) {
            $value = $notification->tr_currency->getValue();
        }

        if (!is_string($value) || trim($value) === '') {
            return true;
        }

        $notificationCurrency = strtoupper(trim($value));

        $orderCurrency = $order->getBaseCurrencyCode();

        if (null === $orderCurrency) {
            return true;
        }

        return strtoupper(trim($orderCurrency)) === $notificationCurrency;
    }
}
