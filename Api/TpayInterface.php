<?php

declare(strict_types=1);

namespace Tpay\Magento2\Api;

/** @api */
interface TpayInterface
{
    public const CODE = 'Tpay_Magento2';
    public const GROUP = 'group';
    public const CHANNEL = 'channel';
    public const BLIK_CODE = 'blik_code';
    public const TERMS_ACCEPT = 'accept_tos';
    public const CARDDATA = 'card_data';
    public const CARD_SAVE = 'card_save';
    public const CARD_ID = 'card_id';
    public const CARD_VENDOR = 'card_vendor';
    public const SHORT_CODE = 'short_code';

    public function isCustomerLoggedIn(): bool;

    /** @param string $orderId */
    public function getCustomerId($orderId);

    /** @param string $orderId */
    public function isCustomerGuest($orderId);

    /** Return url to redirect after placed order */
    public function getPaymentRedirectUrl(): string;

    /** Check that the BLIK Level 0 should be active on a payment channels list */
    public function checkBlikLevel0Settings(): bool;
}
