<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use tpaycom\magento2basic\lib\Curl;

/**
 * Class Transaction
 *
 * @package tpaycom\magento2basic\Model
 */
class Transaction
{
    /**
     * Blik channel id in tpay.com
     *
     * @var string
     */
    const  BLIK_CHANNEL = '64';
    /**
     * API tpay.com url
     *
     * @var string
     */
    private $urlApi = 'https://secure.tpay.com/api/gw';
    /**
     * API password
     *
     * @var  string
     */
    private $apiPassword;

    /**
     * API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * Transaction constructor.
     *
     * @param string $apiPassword
     * @param string $apiKey
     */
    public function __construct($apiPassword, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->apiPassword = $apiPassword;
    }

    /** Generate transaction for BLIK
     *  Return id transaction or false
     *
     * @param $transactionData
     *
     * @param $channel
     * @return bool|string
     */
    public function createTransaction($transactionData, $channel)
    {
        $transactionData['api_password'] = $this->apiPassword;
        $transactionData['kanal'] = $channel;
        $transactionData['json'] = '1';

        $url = $this->urlApi . '/' . $this->apiKey . '/transaction/create';

        $response = Curl::doCurlRequest($url, $transactionData);
        $response = json_decode($response, true);

        if (!$response || $response['result'] === 0) {
            return false;
        }
        return $response;
    }

    /**
     * Send BLIK code for a generated transaction
     *
     * @param $transactionId
     * @param $blikCode
     *
     * @return bool
     */
    public function sendBlikCode($transactionId, $blikCode)
    {
        $transactionData['code'] = $blikCode;
        $transactionData['title'] = $transactionId;
        $transactionData['api_password'] = $this->apiPassword;
        $transactionData['json'] = '1';
        $url = "{$this->urlApi}/{$this->apiKey}/transaction/blik";

        $response = Curl::doCurlRequest($url, $transactionData);

        if (!$response) {
            return false;
        }
        $resp = json_decode($response, true);

        return ((int)$resp['result'] === 1);
    }
}
