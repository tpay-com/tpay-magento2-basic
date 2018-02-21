<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\lib\PaymentBasicFactory;
use tpaycom\magento2basic\lib\ResponseFields;
use tpaycom\magento2basic\Service\TpayService;

/**
 * Class Notification
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
class Notification extends Action
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
     * @var PaymentBasicFactory
     */
    protected $paymentBasicFactory;

    /**
     * @var TpayService
     */
    protected $tpayService;

    protected $request;

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
        PaymentBasicFactory $paymentBasicFactory,
        TpayService $tpayService
    ) {
        $this->tpay = $tpayModel;
        $this->remoteAddress = $remoteAddress;
        $this->paymentBasicFactory = $paymentBasicFactory;
        $this->tpayService = $tpayService;

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
            $paymentBasic = $this->paymentBasicFactory->create(['merchantId' => $id, 'merchantSecret' => $code]);
            $params = $this->getRequest()->getParams();
            if ($checkServer === false) {
                $paymentBasic->disableServerValidation();
            }
            if ($checkProxy === true) {
                $paymentBasic->enableProxyValidation();
                $forwardedIP = $this->getRequest()->getServer('HTTP_X_FORWARDED_FOR');
            }
            $validParams = $paymentBasic->checkPayment($this->remoteAddress->getRemoteAddress(), $forwardedIP, $params);
            $orderId = base64_decode($validParams[ResponseFields::TR_CRC]);
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
}
