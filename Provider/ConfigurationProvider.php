<?php

declare(strict_types=1);

namespace Tpay\Magento2\Provider;

use Composer\InstalledVersions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Api\TpayConfigInterface;

class ConfigurationProvider implements TpayConfigInterface
{
    private const BASE_CONFIG_PATH = 'payment/tpaycom_magento2basic/';

    protected $termsURL = 'https://secure.tpay.com/regulamin.pdf';
    protected $termsEnURL = 'https://tpay.com/user/assets/files_for_download/payment-terms-and-conditions.pdf';
    protected $regulationsURL = 'https://tpay.com/user/assets/files_for_download/klauzula-informacyjna-platnik.pdf';
    protected $regulationsEnURL = 'https://tpay.com/user/assets/files_for_download/information-clause-payer.pdf';

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductMetadataInterface */
    protected $productMetadataInterface;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Resolver */
    private $localeResolver;

    public function __construct(ScopeConfigInterface $scopeConfig, ProductMetadataInterface $productMetadataInterface, StoreManagerInterface $storeManager, Resolver $localeResolver)
    {
        $this->scopeConfig = $scopeConfig;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
    }

    public function isTpayActive(): bool
    {
        return (bool) $this->getConfigData('active');
    }

    public function getBlikLevelZeroStatus(): bool
    {
        return (bool) $this->getConfigData('general_settings/blik_level_zero');
    }

    public function getTitle(): ?string
    {
        return $this->getConfigData('general_settings/title');
    }

    public function getApiKey(): ?string
    {
        return $this->getConfigData('originapi_settings/api_key_tpay');
    }

    public function getCardApiKey(): ?string
    {
        return $this->getConfigData('cardpayment_settings/cardpayment_originapi_settings/card_api_key_tpay');
    }

    public function getApiPassword(): ?string
    {
        return $this->getConfigData('originapi_settings/api_password');
    }

    public function getCardApiPassword(): ?string
    {
        return $this->getConfigData('cardpayment_settings/cardpayment_originapi_settings/card_api_password');
    }

    public function getInvoiceSendMail(): string
    {
        return $this->getConfigData('sale_settings/send_invoice_email');
    }

    public function getTermsURL(): string
    {
        if ('pl' == substr($this->localeResolver->getLocale(), 0, 2)) {
            return $this->termsURL;
        }

        return $this->termsEnURL;
    }

    public function getRegulationsURL(): string
    {
        if ('pl' == substr($this->localeResolver->getLocale(), 0, 2)) {
            return $this->regulationsURL;
        }

        return $this->regulationsEnURL;
    }

    public function getOpenApiPassword(): ?string
    {
        return $this->getConfigData('openapi_settings/open_api_password');
    }

    public function getMerchantId(): ?int
    {
        return (int) $this->getConfigData('general_settings/merchant_id');
    }

    public function getOpenApiClientId(): ?string
    {
        return $this->getConfigData('openapi_settings/open_api_client_id');
    }

    public function getSecurityCode(?int $storeId = null): ?string
    {
        return $this->getConfigData('general_settings/security_code', $storeId);
    }

    public function onlyOnlineChannels(): bool
    {
        return (bool) $this->getConfigData('general_settings/show_payment_channels_online');
    }

    public function redirectToChannel(): bool
    {
        return (bool) $this->getConfigData('general_settings/redirect_directly_to_channel');
    }

    public function getCardTitle(): ?string
    {
        return $this->getConfigData('cardpayment_settings/card_title') ?? '';
    }

    public function isOriginApiEnabled(): bool
    {
        return (bool) $this->getConfigData('originapi_settings/origin_api_active');
    }

    public function isOpenApiEnabled(): bool
    {
        return (bool) $this->getConfigData('openapi_settings/open_api_active');
    }

    public function isCardEnabled(): bool
    {
        return (bool) $this->getConfigData('cardpayment_settings/cardpayment_api_active');
    }

    public function isOriginApiCardUse(): bool
    {
        return (bool) $this->getConfigData('cardpayment_settings/cardpayment_origin_api_use');
    }

    public function useSandboxMode(?int $storeId = null): bool
    {
        return (bool) $this->getConfigData('general_settings/use_sandbox', $storeId);
    }

    public function getMinOrderTotal(): int
    {
        return (int) $this->getConfigData('sale_settings/min_order_total');
    }

    public function getMaxOrderTotal(): int
    {
        return (int) $this->getConfigData('sale_settings/max_order_total');
    }

    public function getCardSaveEnabled(): bool
    {
        return (bool) $this->getConfigData('cardpayment_settings/card_save_enabled');
    }

    public function getRSAKey(): ?string
    {
        return $this->getConfigData('cardpayment_settings/rsa_key');
    }

    public function getHashType(): ?string
    {
        return $this->getConfigData('cardpayment_settings/cardpayment_originapi_settings/hash_type');
    }

    public function getVerificationCode(): ?string
    {
        return $this->getConfigData('cardpayment_settings/cardpayment_originapi_settings/verification_code');
    }

    public function isAllowSpecific(): bool
    {
        return (bool) $this->getConfigData('sale_settings/allowspecific') ?? false;
    }

    public function getSpecificCountry(): array
    {
        return $this->getConfigData('sale_settings/specificcountry') ? explode(',', $this->getConfigData('sale_settings/specificcountry')) : [];
    }

    public function isCartValid(?float $grandTotal = null): bool
    {
        $minAmount = $this->getConfigData('sale_settings/min_order_total');
        $maxAmount = $this->getConfigData('sale_settings/max_order_total');

        if ($grandTotal && ($grandTotal < $minAmount || ($maxAmount && $grandTotal > $maxAmount))) {
            return false;
        }

        if (!$this->getMerchantId()) {
            return false;
        }

        return !is_null($grandTotal);
    }

    public function getMagentoVersion(): string
    {
        return $this->productMetadataInterface->getVersion();
    }

    public function buildMagentoInfo(): string
    {
        return sprintf(
            'magento2:%s|tpay-openapi-php:%s|tpay-php:%s|magento2basic:%s|PHP:%s',
            $this->getMagentoVersion(),
            InstalledVersions::getPrettyVersion('tpay-com/tpay-openapi-php'),
            InstalledVersions::getPrettyVersion('tpay-com/tpay-php'),
            $this->getTpayPluginVersion(),
            phpversion()
        );
    }

    public function getConfigData($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::BASE_CONFIG_PATH.$field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isPlnPayment(): bool
    {
        if ($this->getConfigData('sale_settings/bank_payments_view')) {
            return 'PLN' == $this->storeManager->getStore()->getBaseCurrencyCode();
        }

        return 'PLN' == $this->storeManager->getStore()->getBaseCurrencyCode() && 'PLN' == $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    private function getTpayPluginVersion(): string
    {
        $dir = __DIR__.'/../.version';
        if (file_exists($dir)) {
            $version = file_get_contents(__DIR__.'/../.version');

            return rtrim($version, "\n");
        }

        return 'n/a';
    }
}
