<?php

/**
 * @category    payment gateway
 * @package     tpaycom_magento2basic
 * @author      tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\lib;

/**
 * Class Curl
 *
 * @package tpaycom\magento2basic\lib
 */
class Curl
{
    /**
     * Last executed curl info
     *
     * @return mixed
     */
    private static $curlInfo;

    /**
     * Last executed cURL error
     *
     * @var string
     */
    private static $curlError = '';

    /**
     * Last executed cURL errno
     *
     * @var string
     */
    private static $curlErrno = '';

    /**
     * Send POST request
     *
     * @param string $url
     * @param array  $postData
     *
     * @return bool|mixed
     */
    public static function doCurlRequest($url, $postData = [])
    {
        if (!function_exists('curl_init') || !function_exists('curl_exec')) {
            return false;
        }

        try {
            $ch = curl_init();
            $lastValue = libxml_disable_entity_loader(true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $curlRes           = curl_exec($ch);
            static::$curlInfo  = curl_getinfo($ch);
            static::$curlError = curl_error($ch);
            static::$curlErrno = curl_errno($ch);
        } catch (\Exception $e) {
            return false;
        }
        curl_close($ch);
        libxml_disable_entity_loader($lastValue);

        return static::checkResponse($curlRes);
    }

    /**
     * Check curl response
     *
     * @param $curlRes
     *
     * @return bool
     */
    public static function checkResponse($curlRes)
    {

        if (static::$curlInfo['http_code'] !== 200 || $curlRes === false) {
            return false;
        }

        return $curlRes;
    }
}
