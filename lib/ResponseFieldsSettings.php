<?php

/**
 * @category    payment gateway
 * @package     tpaycom_magento2basic
 * @author      tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\lib;

/**
 * Class ResponseFieldsSettings
 *
 * @package tpaycom\magento2basic\lib
 */
class ResponseFieldsSettings
{
    /**
     * List of fields available in response for basic payment
     *
     * @var array
     */
    public static $fields = [
        /**
         * The transaction ID assigned by the system Transferuj
         */
        ResponseFields::TR_ID     => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [Type::STRING],
        ],
        /**
         * Date of transaction.
         */
        ResponseFields::TR_DATE   => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [Type::STRING],

        ],
        /**
         * The secondary parameter to the transaction identification.
         */
        ResponseFields::TR_CRC    => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [Type::STRING],
        ],
        /**
         * Transaction amount.
         */
        ResponseFields::TR_AMOUNT => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::FLOAT,
            FieldProperties::VALIDATION => [Type::FLOAT],
        ],
        /**
         * The amount paid for the transaction.
         * Note: Depending on the settings, the amount paid can be different than transactions
         * eg. When the customer does overpayment.
         */
        ResponseFields::TR_PAID   => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::FLOAT,
            FieldProperties::VALIDATION => [Type::FLOAT],
        ],
        /**
         * Description of the transaction.
         */
        ResponseFields::TR_DESC   => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [Type::STRING],
        ],
        /**
         * Transaction status: TRUE in the case of the correct result or FALSE in the case of an error.
         * Note: Depending on the settings, the transaction may be correct status,
         * even if the amount paid is different from the amount of the transaction!
         * Eg. If the Seller accepts the overpayment or underpayment threshold is set.
         */
        ResponseFields::TR_STATUS => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [FieldProperties::OPTIONS],
            FieldProperties::OPTIONS    => [0, 1, true, false, 'TRUE', 'FALSE'],
        ],
        /**
         * Transaction error status.
         * Could have the following values:
         * - none
         * - overpay
         * - surcharge
         */
        ResponseFields::TR_ERROR  => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [FieldProperties::OPTIONS],
            FieldProperties::OPTIONS    => ['none', 'overpay', 'surcharge'],
        ],
        /**
         * Customer email address.
         */
        ResponseFields::TR_EMAIL  => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => ['email_list'],
        ],
        /**
         * The checksum verifies the data sent to the payee.
         * It is built according to the following scheme using the MD5 hash function:
         * MD5(id + tr_id + tr_amount + tr_crc + security code)
         */
        ResponseFields::MD5SUM    => [
            FieldProperties::REQ        => true,
            FieldProperties::TYPE       => Type::STRING,
            FieldProperties::VALIDATION => [Type::STRING, 'maxlength_32', 'minlength_32'],
        ],
        /**
         * Transaction marker indicates whether the transaction was executed in test mode:
         * 1 – in test mode
         * 0 – in normal mode
         */
        ResponseFields::TEST_MODE => [
            FieldProperties::REQ        => false,
            FieldProperties::TYPE       => Type::INT,
            FieldProperties::VALIDATION => [FieldProperties::OPTIONS],
            FieldProperties::OPTIONS    => [0, 1],
        ],
        /**
         * The parameter is sent only when you use a payment channel or MasterPass or V.me.
         * Could have the following values: „masterpass” or „vme”
         */
        ResponseFields::WALLET    => [
            FieldProperties::REQ  => false,
            FieldProperties::TYPE => Type::STRING,
        ],
    ];

}
