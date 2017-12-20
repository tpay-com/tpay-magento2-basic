<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\lib\Curl;
use Magento\Framework\Validator\Exception;

class BasicRefunds
{
    const API_URL = 'https://secure.tpay.com/api/gw/';
    private $apiKey;
    private $apiPass;

    /**
     * Refunds constructor.
     * @param $apiKey
     * @param $apiPass
     */

    public function __construct(
        $apiKey,
        $apiPass
    ) {
        $this->apiKey = $apiKey;
        $this->apiPass = $apiPass;
    }

    /**
     * @param InfoInterface $payment
     * @param double $amount
     * @return bool
     * @throws \Exception
     */
    public function makeRefund($payment, $amount)
    {
        $params['title'] = $payment->getParentTransactionId();
        $params['chargeback_amount'] = number_format($amount, 2);
        $params['api_password'] = $this->apiPass;
        $params['json'] = 'true';

        $result = (array)json_decode(Curl::doCurlRequest(static::API_URL . $this->apiKey . '/chargeback/any', $params));

        if ((int)$result['result'] === 1) {
            return true;
        } else {
            $errCode = isset($result['err']) ? ' error code: ' . $result['err'] : '';
            throw new Exception(__('Payment refunding error. -' . $errCode));
        }

    }


}
