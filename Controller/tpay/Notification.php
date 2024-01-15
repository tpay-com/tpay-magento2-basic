<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Model\Order;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Service\TpayTokensService;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;

class Notification extends Action implements CsrfAwareActionInterface
{
    /** @var TpayInterface */
    protected $tpay;

    /** @var RemoteAddress */
    protected $remoteAddress;

    /** @var TpayService */
    protected $tpayService;

    protected $request;

    /** @var TpayTokensService */
    private $tokensService;

    public function __construct(Context $context, RemoteAddress $remoteAddress, TpayInterface $tpayModel, TpayService $tpayService, TpayTokensService $tokensService)
    {
        $this->tpay = $tpayModel;
        $this->remoteAddress = $remoteAddress;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        Util::$loggingEnabled = false;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $notification = (new JWSVerifiedPaymentNotification($this->tpay->getSecurityCode(), !$this->tpay->useSandboxMode()))->getNotification();
            $notification = $notification->getNotificationAssociative();
            $orderId = base64_decode($notification['tr_crc']);

            if ('PAID' === $notification['tr_status']) {
                $response = $this->getPaidTransactionResponse($orderId);

                return $this->getResponse()->setStatusCode(Http::STATUS_CODE_200)->setContent($response);
            }

            $this->saveCard($notification, $orderId);
            $this->tpayService->SetOrderStatus($orderId, $notification, $this->tpay);

            return $this->getResponse()->setStatusCode(Http::STATUS_CODE_200)->setContent('TRUE');
        } catch (Exception $e) {
            Util::log('Notification exception', "{$e->getMessage()} in file {$e->getFile()} line: {$e->getLine()} \n\n {$e->getTraceAsString()}");

            return $this->getResponse()->setStatusCode(Http::STATUS_CODE_500)->setContent('FALSE');
        }
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check if the order has been canceled and get response to Tpay server.
     *
     * @throws Exception
     *
     * @return string response for Tpay server
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
            $token = $this->tokensService->getWithoutAuthCustomerTokens((int) $order->getCustomerId(), $notification['tr_crc']);
            if (!empty($token)) {
                $this->tokensService->updateTokenById((int) $token['tokenId'], $notification['card_token']);
            }
        }
    }
}
