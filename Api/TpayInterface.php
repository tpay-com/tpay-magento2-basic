<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Api;

/**
 * @api
 */
interface TpayInterface
{
    public const CODE = 'tpaycom_magento2basic';
    public const CHANNEL = 'group';
    public const BLIK_CODE = 'blik_code';
    public const TERMS_ACCEPT = 'accept_tos';

    /** Return string for redirection */
    public function getRedirectURL(): string;

    /** Return data for form */
    public function getTpayFormData(?string $orderId = null): array;

    public function getApiPassword(): ?string;

    public function getApiKey(): ?string;

    public function getSecurityCode(): string;

    public function getMerchantId(): int;

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

    public function useSandboxMode(): bool;

    /** Check if checkout amount is in range of installments payment channel */
    public function getInstallmentsAmountValid(): bool;
}
