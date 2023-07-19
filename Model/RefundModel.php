<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use tpayLibs\src\_class_tpay\Refunds\BasicRefunds;
use tpayLibs\src\_class_tpay\Utilities\Util;

/**
 * Class Refund
 *
 * @package tpaycom\magento2basic\Model
 */
class RefundModel extends BasicRefunds
{
    /**
     * Refund constructor.
     *
     * @param string $apiPassword
     * @param string $apiKey
     * @param int $merchantId
     * @param string $merchantSecret
     */
    public function __construct($apiPassword, $apiKey, $merchantId, $merchantSecret, $isProd = true)
    {
        $this->trApiKey = $apiKey;
        $this->trApiPass = $apiPassword;
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
        if (!$isProd) {
            $this->apiURL = 'https://secure.sandbox.tpay.com/api/gw/';
        }
    }


    /**
     * @param InfoInterface $payment
     * @param double $amount
     * @return bool
     * @throws \Exception
     */
    public function makeRefund($payment, $amount)
    {
        Util::$loggingEnabled = false;
        $apiResult = $this->setTransactionID($payment->getParentTransactionId())
            ->refundAny(number_format($amount, 2));
        if (isset($apiResult['result']) && (int)$apiResult['result'] === 1) {
            return true;
        } else {
            $errCode = isset($apiResult['err']) ? ' error code: ' . $apiResult['err'] : '';
            throw new Exception(__('Payment refunding error. -' . $errCode));
        }
    }

}
