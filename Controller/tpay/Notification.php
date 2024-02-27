<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Exception;
use Laminas\Http\Response;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Service\TpayTokensService;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;

class Notification implements CsrfAwareActionInterface
{
    /** @var TpayInterface */
    protected $tpay;

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
        $this->tpay = $tpayModel;
        $this->tpayService = $tpayService;
        $this->tokensService = $tokensService;
        $this->storeManager = $storeManager;
        Util::$loggingEnabled = false;
    }

    public function execute(): ResultInterface
    {
        return $this->getNotification();
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
     * @return string response for Tpay server
     * @throws Exception
     *
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
                (int) $order->getCustomerId(),
                $notification['tr_crc']
            );
            if (!empty($token)) {
                $this->tokensService->updateTokenById((int) $token['tokenId'], $notification['card_token']);
            }
        }
    }

    private function getNotification()
    {
        $returnData = null;
        foreach ($this->storeManager->getStores() as $store) {
            [$returnData, $isPassed] = $this->extractNotification($store);
            if ($isPassed) {
                break;
            }
        }

        return $returnData;
    }

    private function extractNotification(StoreInterface $store): array
    {
        $storeId = $store->getStoreId();

        try {
            $notification = (new JWSVerifiedPaymentNotification(
                $this->tpay->getSecurityCode($storeId),
                !$this->tpay->useSandboxMode($storeId)
            ))->getNotification();
            $notification = $notification->getNotificationAssociative();
            $orderId = base64_decode($notification['tr_crc']);

            if ('PAID' === $notification['tr_status']) {
                $response = $this->getPaidTransactionResponse($orderId);

                $returnData = (new Response())->setStatusCode(Response::STATUS_CODE_200)->setContent($response);

                return [$returnData, true];
            }

            $this->saveCard($notification, $orderId);
            $this->tpayService->SetOrderStatus($orderId, $notification, $this->tpay);

            $returnData = (new Response())->setStatusCode(Response::STATUS_CODE_200)->setContent('TRUE');
            $isPassed = true;
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

            $returnData = (new Response())->setStatusCode(Response::STATUS_CODE_200)->setContent('FALSE');
            $isPassed = false;
        }

        return [$returnData, $isPassed];
    }
}
