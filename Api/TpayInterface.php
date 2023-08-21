<?php

namespace tpaycom\magento2basic\Api;

/**
 * @api
 */
interface TpayInterface
{
    const CODE = 'tpaycom_magento2basic';
    const CHANNEL = 'group';
    const BLIK_CODE = 'blik_code';
    const TERMS_ACCEPT = 'accept_tos';

    /**
     * Return string for redirection
     *
     * @return string
     */
    public function getRedirectURL();

    /**
     * Return data for form
     *
     * @param null|int $orderId
     *
     * @return array
     */
    public function getTpayFormData($orderId = null);

    /** @return string */
    public function getApiPassword();

    /** @return string */
    public function getApiKey();

    /** @return string */
    public function getSecurityCode();

    /** @return int */
    public function getMerchantId();

    /**
     * Check that the BLIK Level 0 should be active on a payment channels list
     *
     * @return bool
     */
    public function checkBlikLevel0Settings();

    /** @return bool */
    public function getBlikLevelZeroStatus();

    /** @return bool */
    public function onlyOnlineChannels();

    /** @return bool */
    public function redirectToChannel();

    /**
     * Return url to redirect after placed order
     *
     * @return string
     */
    public function getPaymentRedirectUrl();

    /**
     * Return url for a tpay.com terms
     *
     * @return string
     */
    public function getTermsURL();

    /**
     * Check if send an email about the new invoice to customer
     *
     * @return string
     */
    public function getInvoiceSendMail();

    /**
     * Check if Tpay notification server IP is forwarded by proxy
     *
     * @return bool
     */
    public function getCheckProxy();

    /**
     * Check Tpay notification server IP
     *
     * @return bool
     */
    public function getCheckTpayIP();

    /**
     * Check if checkout amount is in range of installments payment channel
     *
     * @return bool
     */
    public function getInstallmentsAmountValid();
}
