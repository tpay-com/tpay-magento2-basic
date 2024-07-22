<?php

declare(strict_types=1);

namespace Tpay\Magento2\Api;

/** @api */
interface TpayConfigInterface
{
    public function isTpayActive(): bool;

    public function getTitle(): ?string;

    public function getCardTitle(): ?string;

    public function isOriginApiEnabled(?int $storeId = null): bool;

    public function isOpenApiEnabled(?int $storeId = null): bool;

    public function isCardEnabled(): bool;

    public function isOriginApiCardUse(): bool;

    public function getApiPassword(?int $storeId = null): ?string;

    public function getOpenApiPassword(?int $storeId = null): ?string;

    public function getApiKey(?int $storeId = null): ?string;

    public function getSecurityCode(?int $storeId = null): ?string;

    public function getOpenApiClientId(?int $storeId = null): ?string;

    public function getMerchantId(?int $storeId = null): ?int;

    public function getBlikLevelZeroStatus(): bool;

    public function onlyOnlineChannels(): bool;

    public function redirectToChannel(): bool;

    /** Return url for a tpay.com terms */
    public function getTermsURL(): string;

    public function getRegulationsURL(): string;

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

    public function isPlnPayment(): bool;
}
