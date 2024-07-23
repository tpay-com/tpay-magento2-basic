<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Exception;
use Laminas\Http\Response;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OriginApi\Utilities\Util;
use Tpay\OriginApi\Webhook\JWSVerifiedPaymentNotification as OriginJWSVerifiedPaymentNotification;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;

class Notification implements CsrfAwareActionInterface
{
    /** @var TpayInterface */
    protected $tpay;

    /** @var TpayConfigInterface */
    protected $tpayConfig;

    /** @var TpayService */
    protected $tpayService;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var ResponseInterface */
    private $response;

    public function __construct(
        TpayInterface $tpayModel,
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        TpayTokensService $tokensService,
        ResponseInterface $response
    ) {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->response = $response;
        Util::$loggingEnabled = false;
    }

    public function execute(): ?Response
    {
        if (isset($_POST['card'])) {
            $orderId = base64_decode($_POST['order_id']);

            return $this->extractCardNotification($this->getOrderStore($orderId));
        }

        $orderId = base64_decode($_POST['tr_crc']);

        return $this->extractNotification($this->getOrderStore($orderId));
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check if the order has been canceled and get response to Tpay server.
     *
     * @throws Exception
     */
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

    private function extractNotification(?int $storeId = null): Response
    {
        try {
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

            return $this->response->setStatusCode(Response::STATUS_CODE_200)->setContent('TRUE');
        } catch (Exception $e) {
            $this->handleException($e);

            return $this->response->setStatusCode(Response::STATUS_CODE_400)->setContent('FALSE');
        }
    }

    private function extractCardNotification(?int $storeId = null): ?Response
    {
        try {
            $notification = (new OriginJWSVerifiedPaymentNotification(
                $this->tpayConfig->getSecurityCode($storeId),
                !$this->tpayConfig->useSandboxMode($storeId)
            ))->getNotification();

            $orderId = base64_decode($notification['order_id']);

            $this->tpayService->setCardOrderStatus($orderId, $notification, $this->tpayConfig);
            $this->saveOriginCard($notification, $orderId);

            return $this->response->setStatusCode(Response::STATUS_CODE_200)->setContent('TRUE');
        } catch (Exception $e) {
            $this->handleException($e);

            return $this->response->setStatusCode(Response::STATUS_CODE_400)->setContent('FALSE');
        }
    }

    private function handleException(Exception $e)
    {
        Util::log(
            'Notification exception',
            sprintf(
                '%s in file %s line: %d \n\n %s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            )
        );
    }

    private function getOrderStore(string $orderId): ?int
    {
        $order = $this->tpayService->getOrderById($orderId);

        return $order->getStoreId() ? (int) $order->getStoreId() : null;
    }
}
