<?php

/**
 * @category    payment gateway
 * @package     tpaycom_magento2basic
 * @author      tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\lib;

/**
 * Class PaymentBasic
 *
 * Class handles bank transfer payment through tpay.com panel
 *
 * @package tpaycom\magento2basic\lib
 */
class PaymentBasic
{
    /**
     * Merchant id
     *
     * @var int
     */
    protected $merchantId;

    /**
     * Merchant secret
     *
     * @var string
     */
    private $merchantSecret;

    /**
     * tpay.com response IP
     *
     * @var array
     */
    private $secureIP = [
        '195.149.229.109',
        '148.251.96.163',
        '178.32.201.77',
        '46.248.167.59',
        '46.29.19.106',
    ];

    /**
     * PaymentBasic class constructor for payment:
     * - basic from tpay.com panel
     * - with bank selection in merchant shop
     * - eHat
     *
     * @param string|bool $merchantId     merchant id
     * @param string|bool $merchantSecret merchant secret
     */
    public function __construct($merchantId = false, $merchantSecret = false)
    {
        if ($merchantId !== false) {
            $this->merchantId = $merchantId;
        }

        if ($merchantSecret !== false) {
            $this->merchantSecret = $merchantSecret;
        }

        Validate::validateMerchantId($this->merchantId);
        Validate::validateMerchantSecret($this->merchantSecret);
    }

    /**
     * Check cURL request from tpay.com server after payment.
     * This method check server ip, required fields and md5 checksum sent by payment server.
     * Display information to prevent sending repeated notifications.
     *
     * @param string $remoteAddress remote address
     * @param null|array   $params
     *
     * @return array
     * @throws TException
     */

    public function checkPayment($remoteAddress, $params = null)
    {

        $res = Validate::getResponse($params);

        $checkMD5 = $this->checkMD5(
            $res['md5sum'],
            $res['tr_id'],
            number_format($res['tr_amount'], 2, '.', ''),
            $res['tr_crc']
        );

        if ($this->checkServer($remoteAddress) === false) {
            throw new TException('Request is not from secure server');
        }

        if ($checkMD5 === false) {
            throw new TException('MD5 checksum is invalid');
        }

        return $res;
    }



    /**
     * Check md5 sum to confirm value of payment amount
     *
     * @param string $md5sum            md5 sum received from tpay.com
     * @param string $transactionId     transaction id
     * @param string $transactionAmount transaction amount
     * @param string $crc               transaction crc
     *
     * @throws TException
     */
    public function validateSign($md5sum, $transactionId, $transactionAmount, $crc)
    {
        if ($md5sum !== md5($this->merchantId.$transactionId.$transactionAmount.$crc.$this->merchantSecret)) {
            throw new TException('Invalid checksum');
        }
    }

    /**
     * Check if request is called from secure tpay.com server
     *
     * @param $remoteAddress
     *
     * @return bool
     */
    private function checkServer($remoteAddress)
    {
        if (!isset($remoteAddress) || !in_array($remoteAddress, $this->secureIP)) {
            return false;
        }

        return true;
    }

    /**
     * Check md5 sum to validate tpay.com response.
     * The values of variables that md5 sum includes are available only for
     * merchant and tpay.com system.
     *
     * @param string $md5sum            md5 sum received from tpay.com
     * @param string $transactionId     transaction id
     * @param float  $transactionAmount transaction amount
     * @param string $crc               transaction crc
     *
     * @return bool
     */
    private function checkMD5($md5sum, $transactionId, $transactionAmount, $crc)
    {
        if (!is_string($md5sum) || strlen($md5sum) !== 32) {
            return false;
        }

        return
            ($md5sum === md5($this->merchantId.$transactionId.$transactionAmount.$crc.$this->merchantSecret));
    }
}
