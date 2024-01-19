<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Api;

/**
 * @api
 */
interface TpayInterface
{
    public const CODE = 'tpaycom_magento2basic';
    public const GROUP = 'group';
    public const CHANNEL = 'channel';
    public const BLIK_CODE = 'blik_code';
    public const TERMS_ACCEPT = 'accept_tos';
    public const CARDDATA = 'card_data';
    public const CARD_SAVE = 'card_save';
    public const CARD_ID = 'card_id';
    public const CARD_VENDOR = 'card_vendor';
    public const SHORT_CODE = 'short_code';

    /** Return string for redirection */
    public function getRedirectURL(): string;

    /** Return data for form */
    public function getTpayFormData(?string $orderId = null): array;

    public function getCardTitle(): ?string;

    public function isOriginApiEnabled(): bool;

    public function isOpenApiEnabled(): bool;

    public function isCardEnabled(): bool;

    public function isOriginApiCardUse(): bool;

    public function getApiPassword(): ?string;

    public function getOpenApiPassword(): ?string;

    public function getApiKey(): ?string;

    public function getSecurityCode(?int $storeId = null): ?string;

    public function getOpenApiClientId(): ?string;

    public function getMerchantId(): ?int;

    /** Check that the BLIK Level 0 should be active on a payment channels list */
    public function checkBlikLevel0Settings(): bool;

    public function getBlikLevelZeroStatus(): bool;

    public function onlyOnlineChannels(): bool;

    public function redirectToChannel(): bool;

    /** Return url to redirect after placed order */
    public function getPaymentRedirectUrl(): string;

    /** Return url for a tpay.com terms */
    public function getTermsURL(): string;

    /** Check if send an email about the new invoice to customer */
    public function getInvoiceSendMail(): string;

    public function useSandboxMode(?int $storeId = null): bool;

    /** Check if checkout amount is in range of installments payment channel */
    public function getInstallmentsAmountValid(): bool;

    public function getCardApiKey(): ?string;

    public function getCardApiPassword(): ?string;

    public function getCardSaveEnabled(): bool;

    public function getCheckoutCustomerId(): ?string;

    public function getRSAKey(): ?string;

    public function isCustomerLoggedIn(): bool;

    public function getHashType(): ?string;

    public function getVerificationCode(): ?string;

    public function isAllowSpecific(): bool;

    public function getSpecificCountry(): array;

    /** @param string $orderId */
    public function getCustomerId($orderId);

    /** @param string $orderId */
    public function isCustomerGuest($orderId);
}
