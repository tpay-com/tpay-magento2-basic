<?php

namespace Tpay\Magento2\Model\ApiFacade\Refund;

use Magento\Framework\Validator\Exception;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\OriginApi\Refunds\CardRefunds;
use Tpay\OriginApi\Utilities\Util;

class RefundCardOriginApi extends CardRefunds
{
    public function __construct(TpayConfigInterface $tpay, ?int $storeId = null)
    {
        Util::$loggingEnabled = false;
        $this->cardApiKey = $tpay->getCardApiKey($storeId);
        $this->cardApiPass = $tpay->getCardApiPassword($storeId);
        $this->cardVerificationCode = $tpay->getVerificationCode($storeId);
        $this->cardKeyRSA = $tpay->getRSAKey($storeId);
        $this->cardHashAlg = $tpay->getHashType($storeId);
        parent::__construct();
        if ($tpay->useSandboxMode()) {
            $this->cardsApiURL = 'https://secure.sandbox.tpay.com/api/cards/';
        }
    }

    public function makeCardRefund($payment, $amount, $currency = '985')
    {
        $transactionId = $payment->getParentTransactionId();
        $this->setAmount($amount)->setCurrency($currency);
        $result = $this->refund($transactionId, __('Zwrot do zamówienia ').$payment->getOrder()->getRealOrderId());

        if (1 === (int) $result['result'] && isset($result['status']) && 'correct' === $result['status']) {
            return $result;
        }
        $errDesc = isset($result['err_desc']) ? ' error description: '.$result['err_desc'] : '';
        $errCode = isset($result['err_code']) ? ' error code: '.$result['err_code'] : '';
        $reason = isset($result['reason']) ? ' reason: '.$result['reason'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode.$errDesc.$reason));
    }
}
