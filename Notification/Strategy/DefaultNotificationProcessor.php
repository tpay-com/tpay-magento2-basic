<?php

namespace Tpay\Magento2\Notification\Strategy;

use Laminas\Http\Response;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification;

class DefaultNotificationProcessor implements NotificationProcessorInterface
{
    /** @var TpayConfigInterface */
    protected $tpayConfig;

    /** @var TpayService */
    protected $tpayService;

    public function __construct(TpayConfigInterface $tpayConfig, TpayService $tpayService)
    {
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
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
            $response = $this->getPaidTransactionResponse($orderId);

            return $this->response->setStatusCode(Response::STATUS_CODE_200)->setContent($response);
        }

        $this->saveCard($notification, $orderId);
        $this->tpayService->setOrderStatus($orderId, $notification, $this->tpayConfig);
    }
}
