<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\NotificationModel;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Model\NotificationModelFactory;
use tpayLibs\src\_class_tpay\Utilities\Util;
use Magento\Sales\Model\Order;

/**
 * Class Notification
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
class Notification extends Action implements CsrfAwareActionInterface
{
    /**
     * @var TpayInterface
     */
    protected $tpay;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var bool
     */
    protected $emailNotify = false;

    /**
     * @var NotificationModelFactory
     */
    protected $notificationFactory;

    /**
     * @var TpayService
     */
    protected $tpayService;

    protected $request;

    /**
     * @var NotificationModel
     */
    protected $NotificationHandler;

    /**
     * {@inheritdoc}
     *
     * @param RemoteAddress $remoteAddress
     * @param TpayInterface $tpayModel
     */
    public function __construct(
        Context $context,
        RemoteAddress $remoteAddress,
        TpayInterface $tpayModel,
        NotificationModelFactory $notificationModelFactory,
        TpayService $tpayService
    ) {
        $this->tpay = $tpayModel;
        $this->remoteAddress = $remoteAddress;
        $this->notificationFactory = $notificationModelFactory;
        $this->tpayService = $tpayService;
        Util::$loggingEnabled = false;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        try {
            $id = $this->tpay->getMerchantId();
            $code = $this->tpay->getSecurityCode();
            $checkServer = $this->tpay->getCheckTpayIP();
            $checkProxy = $this->tpay->getCheckProxy();
            $forwardedIP = null;
            $this->NotificationHandler = $this->notificationFactory->create(
                [
                    'merchantId' => $id,
                    'merchantSecret' => $code
                ]
            );
            if ($checkServer === false) {
                $this->NotificationHandler->disableValidationServerIP();
            }
            if ($checkProxy === true) {
                $this->NotificationHandler->enableForwardedIPValidation();
            }
            $validParams = $this->NotificationHandler->checkPayment('');
            $orderId = base64_decode($validParams['tr_crc']);
            if ($validParams['tr_status'] === 'PAID') {
                $response = $this->getPaidTransactionResponse($orderId);

                return $this
                    ->getResponse()
                    ->setStatusCode(Http::STATUS_CODE_200)
                    ->setContent($response);
            }
            $this->tpayService->SetOrderStatus($orderId, $validParams, $this->tpay);

            return
                $this
                    ->getResponse()
                    ->setStatusCode(Http::STATUS_CODE_200)
                    ->setContent('TRUE');

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request)
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request)
    {
        return true;
    }

    /**
     * Check if the order has been canceled and get response to Tpay server.
     * @param int $orderId
     * @return string response for Tpay server
     * @throws \Exception
     */
    protected function getPaidTransactionResponse($orderId)
    {
        $order = $this->tpayService->getOrderById($orderId);
        if (!$order->getId()) {
            throw new \Exception('Unable to get order by orderId %s', $orderId);
        }
        if ($order->getState() === Order::STATE_CANCELED) {
            return 'FALSE';
        }

        return 'TRUE';
    }

}
