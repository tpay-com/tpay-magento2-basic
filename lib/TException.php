<?php

/**
 * @category    payment gateway
 * @package     tpaycom_magento2basic
 * @author      tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\lib;

/**
 * Class TException
 *
 * @package tpaycom\magento2basic\lib
 */
class TException extends \Exception
{
    /**
     * @param string $message error message
     * @param int    $code    error code
     */
    public function __construct($message, $code = 0)
    {
        $message .= ' in file '.$this->getFile().' line: '.$this->getLine();
        $this->message = $code.' : '.$message;

        return $code.' : '.$message;
    }
}
