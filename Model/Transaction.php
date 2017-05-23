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
     * API tpay.com url
     *
     * @var string
     */
    private $urlApi = 'https://secure.tpay.com/api/gw';

    /**
     * Blik channel id in tpay.com
     *
     * @var string
     */
    const  BLIK_CHANNEL = '64';

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
        $this->apiKey      = $apiKey;
        $this->apiPassword = $apiPassword;
    }

    /** Generate transaction for BLIK
     *  Return id transaction or false
     *
     * @param $transactionData
     *
     * @return bool|string
     */
    public function createBlikTransaction($transactionData)
    {
        $transactionData['api_password'] = $this->apiPassword;
        $transactionData['kanal']        = static::BLIK_CHANNEL;
        $transactionData['json']         = '1';

        $url = $this->urlApi.'/'.$this->apiKey.'/transaction/create';

        $response = Curl::doCurlRequest($url, $transactionData);

        if (!$response) {
            return false;
        }

        return $this->blikTransactionResult($response);
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
        $transactionData['code']         = $blikCode;
        $transactionData['title']        = $transactionId;
        $transactionData['api_password'] = $this->apiPassword;
        $url                             = "{$this->urlApi}/{$this->apiKey}/transaction/blik";

        libxml_disable_entity_loader(true);

        $response = Curl::doCurlRequest($url, $transactionData);

        if (!$response) {
            return false;
        }

        $xml = new \SimpleXMLElement($response);

        if ((string)$xml->result !== '1') {
            return false;
        }

        return true;
    }

    /**
     * Check response for created BLIK transaction.
     *
     * @param $response
     *
     * @return bool|string
     */
    private function blikTransactionResult($response)
    {
        $response = json_decode($response);

        if ((string)$response->result !== '1') {
            return false;
        }

        return (string)$response->title;
    }
}
