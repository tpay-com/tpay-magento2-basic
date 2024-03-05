<?php

declare(strict_types=1);

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Exception;
use Laminas\Http\Response;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\OriginApi\Utilities\Util;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Service\TpayService;
use TpayCom\Magento2Basic\Service\TpayTokensService;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;

class Notification implements CsrfAwareActionInterface
{
    /** @var TpayInterface */
    protected $tpay;

    /** @var TpayConfigInterface */
    protected $tpayConfig;

    /** @var RemoteAddress */
    protected $remoteAddress;

    /** @var TpayService */
    protected $tpayService;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        TpayInterface $tpayModel,
        TpayService $tpayService,
        TpayTokensService $tokensService,
        StoreManagerInterface $storeManager
    ) {
    public function __construct(Context $context, RemoteAddress $remoteAddress, TpayInterface $tpayModel, TpayConfigInterface $tpayConfig, TpayService $tpayService, TpayTokensService $tokensService, StoreManagerInterface $storeManager)
    {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->remoteAddress = $remoteAddress;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->storeManager = $storeManager;
        Util::$loggingEnabled = false;
    }

    public function execute(): ?Response
    {
        $response = null;

        foreach ($this->storeManager->getStores() as $store) {
            $response = $this->extractNotification($store);

            if (Response::STATUS_CODE_200 === $response->getStatusCode()) {
                break;
            }
        }

        return $response;
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
                $order->getCustomerId(),
                $notification['tr_crc']
            );

            if (!empty($token)) {
                $this->tokensService->updateTokenById((int) $token['tokenId'], $notification['card_token']);
            }
        }
    }

    private function extractNotification(StoreInterface $store): Response
    {
        $storeId = $store->getStoreId();

        try {
            $notification = (new JWSVerifiedPaymentNotification(
                $this->tpay->getSecurityCode($storeId),
                !$this->tpay->useSandboxMode($storeId)
            ))->getNotification();
            $notification = (new JWSVerifiedPaymentNotification($this->tpayConfig->getSecurityCode($storeId), !$this->tpayConfig->useSandboxMode($storeId)))->getNotification();
            $notification = $notification->getNotificationAssociative();
            $orderId = base64_decode($notification['tr_crc']);

            if ('PAID' === $notification['tr_status']) {
                $response = $this->getPaidTransactionResponse($orderId);

                return (new Response())->setStatusCode(Response::STATUS_CODE_200)->setContent($response);
            }

            $this->saveCard($notification, $orderId);
            $this->tpayService->SetOrderStatus($orderId, $notification, $this->tpay);

            return (new Response())->setStatusCode(Response::STATUS_CODE_200)->setContent('TRUE');
        } catch (Exception $e) {
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

            return (new Response())->setStatusCode(Response::STATUS_CODE_400)->setContent('FALSE');
        }
    }
}
