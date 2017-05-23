<?php

/**
 * @category    payment gateway
 * @package     tpaycom_magento2basic
 * @author      tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\lib;

/**
 * Class ResponseFields
 *
 * @package tpaycom\magento2basic\lib
 */
class ResponseFields
{
    const TR_ID     = 'tr_id';
    const TR_DATE   = 'tr_date';
    const TR_CRC    = 'tr_crc';
    const TR_AMOUNT = 'tr_amount';
    const TR_PAID   = 'tr_paid';
    const TR_DESC   = 'tr_desc';
    const TR_STATUS = 'tr_status';
    const TR_ERROR  = 'tr_error';
    const TR_EMAIL  = 'tr_email';
    const MD5SUM    = 'md5sum';
    const TEST_MODE = 'test_mode';
    const WALLET    = 'wallet';
}
