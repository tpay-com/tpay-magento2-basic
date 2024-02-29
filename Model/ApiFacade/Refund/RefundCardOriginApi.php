<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Refund;

use Magento\Framework\Validator\Exception;
use Tpay\OriginApi\Refunds\CardRefunds;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayConfigInterface;

class RefundCardOriginApi extends CardRefunds
{
    public function __construct(TpayConfigInterface $tpay)
    {
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
        $transactionId = $payment->getParentTransactionId();
        $this->setAmount($amount)->setCurrency($currency);
        $result = $this->refund($transactionId, __('Zwrot do zamówienia ').$payment->getOrder()->getRealOrderId());

        if (1 === (int) $result['result'] && isset($result['status']) && 'correct' === $result['status']) {
            return $result['sale_auth'];
        }
        $errDesc = isset($result['err_desc']) ? ' error description: '.$result['err_desc'] : '';
        $errCode = isset($result['err_code']) ? ' error code: '.$result['err_code'] : '';
        $reason = isset($result['reason']) ? ' reason: '.$result['reason'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode.$errDesc.$reason));
    }
}
