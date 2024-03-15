<?php

declare(strict_types=1);

namespace Tpay\Magento2\Api;

/** @api */
interface TpayConfigInterface
{
    public function getTitle(): ?string;

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

    public function getBlikLevelZeroStatus(): bool;

    public function onlyOnlineChannels(): bool;

    public function redirectToChannel(): bool;

    /** Return url for a tpay.com terms */
    public function getTermsURL(): string;

    /** Check if send an email about the new invoice to customer */
    public function getInvoiceSendMail(): string;

    public function useSandboxMode(?int $storeId = null): bool;

    public function getCardApiKey(): ?string;

    public function getCardApiPassword(): ?string;

    public function getCardSaveEnabled(): bool;

    public function getRSAKey(): ?string;

    public function getHashType(): ?string;

    public function getVerificationCode(): ?string;

    public function isAllowSpecific(): bool;

    public function getSpecificCountry(): array;

    public function getMinOrderTotal(): int;

    public function getMaxOrderTotal(): int;

    public function getMagentoVersion(): string;

    public function buildMagentoInfo(): string;
}
