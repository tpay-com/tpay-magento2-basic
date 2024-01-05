<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model\ApiFacade\Refund;

use Magento\Framework\Validator\Exception;
use Tpay\OriginApi\Refunds\CardRefunds;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;

/**
 * Class RefundCardOriginApi
 * @package tpaycom\magento2basic\Model\ApiFacade
 */
class RefundCardOriginApi extends CardRefunds
{
    public function __construct(TpayInterface $tpay)
    {
//
//        $this->apiKey = 'bda5eda723bf1ae71a82e90a249803d3f852248d';
//        $this->apiPass = 'IhZVgraNcZoWPLgA1yQcGMIzquVWWrWtJ';
//        $this->verificationCode = '6680181602d396e640cb091ea5418171';
//        $this->keyRsa = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0NCk1JR2ZNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0R05BRENCaVFLQmdRQ2NLRTVZNU1Wemd5a1Z5ODNMS1NTTFlEMEVrU2xadTRVZm1STS8NCmM5L0NtMENuVDM2ekU0L2dMRzBSYzQwODRHNmIzU3l5NVpvZ1kwQXFOVU5vUEptUUZGVyswdXJacU8yNFRCQkxCcU10TTVYSllDaVQNCmVpNkx3RUIyNnpPOFZocW9SK0tiRS92K1l1YlFhNGQ0cWtHU0IzeHBhSUJncllrT2o0aFJDOXk0WXdJREFRQUINCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQ==';
//        $this->hashType = 'sha512';


//        $this->tpay = $tpay;
//        $this->apiKey = $tpay->getApiPassword();
//        $this->apiPass = $tpay->getApiKey();
//        $this->verificationCode = $tpay->getVerificationCode();
//        $this->keyRsa = $tpay->getRSAKey();
//        $this->hashType = $tpay->getHashType();

        Util::$loggingEnabled = false;
        $this->cardApiKey = $tpay->getCardApiKey();
        $this->cardApiPass = $tpay->getCardApiPassword();
        $this->cardVerificationCode = $tpay->getVerificationCode();
        $this->cardKeyRSA = $tpay->getRSAKey();
        $this->cardHashAlg = $tpay->getHashType();
        parent::__construct();
    }


    public function makeCardRefund($payment, $amount, $currency = '985')
    {
//        $tpayApi = new CardRefundModel($this->apiPass, $this->apiKey, $this->verificationCode, $this->keyRsa, $this->hashType);
        $transactionId = $payment->getParentTransactionId();
        $this->setAmount($amount)->setCurrency($currency);
        $result = $this->refund($transactionId, __('Zwrot do zamówienia ') . $payment->getOrder()->getRealOrderId());

        if (1 === (int)$result['result'] && isset($result['status']) && 'correct' === $result['status']) {
            return $result['sale_auth'];
        }
        $errDesc = isset($result['err_desc']) ? ' error description: ' . $result['err_desc'] : '';
        $errCode = isset($result['err_code']) ? ' error code: ' . $result['err_code'] : '';
        $reason = isset($result['reason']) ? ' reason: ' . $result['reason'] : '';
        throw new Exception(__('Payment refunding error. -' . $errCode . $errDesc . $reason));
    }
}
