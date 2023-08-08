<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Model\RefundModel;
use tpayLibs\src\_class_tpay\Utilities\Util;

class Refund
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiPassword;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $merchantSecret;

    /**
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function makeRefund($payment, $amount)
    {
        Util::$loggingEnabled = false;
        $RefundModel = new RefundModel($this->apiPassword, $this->apiKey, $this->merchantId, $this->merchantSecret);
        $apiResult = $RefundModel
            ->setTransactionID($payment->getParentTransactionId())
            ->refundAny(number_format($amount, 2));
        if (isset($apiResult['result']) && 1 === (int)$apiResult['result']) {
            return true;
        }
        $errCode = isset($apiResult['err']) ? ' error code: '.$apiResult['err'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode));

    }

    /**
     * @param string $merchantSecret
     *
     * @return Refund
     */
    public function setMerchantSecret($merchantSecret)
    {
        $this->merchantSecret = $merchantSecret;

        return $this;
    }

    /**
     * @param int $merchantId
     *
     * @return Refund
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * @param string $apiPassword
     *
     * @return Refund
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * @param string $apiKey
     *
     * @return Refund
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
