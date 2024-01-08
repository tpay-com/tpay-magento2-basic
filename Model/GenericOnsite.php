<?php

namespace tpaycom\magento2basic\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\StoreManager;
use tpaycom\magento2basic\Api\TpayInterface;

class GenericOnsite extends AbstractMethod implements TpayInterface
{
    protected $_title = null;
    protected $_channelId;
    private $storeManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        StoreManager $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );

        $this->_isGateway = true;
        $this->_canRefund = true;
        $this->_canRefundInvoicePartial = true;
        $this->_code = 'generic';
        $this->_title = 'generic';
        $this->storeManager = $storeManager;
    }

    public function setCode(string $code): void
    {
        $this->_code = $code;
    }

    public function setChannelId(int $channelId): void
    {
        $this->_channelId = $channelId;
    }

    public function getChannelId(): int
    {
        return $this->_channelId;
    }

    public function setTitle(string $title): void
    {
        $this->_title = $title;
    }

    public function getConfigData($field, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return parent::getConfigData($field, $storeId);
    }

    public function assignData(DataObject $data): GenericOnsite
    {
        /** @var array<string> $additionalData */
        $additionalData = $data->getData('additional_data');

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('channel', $additionalData['channel']);

        return $this;
    }

    public function isActive($storeId = null)
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->_title ?? $this->getConfigData('title');
    }

    public function getRedirectURL(): string
    {
        return '';
    }

    public function getTpayFormData($orderId = null): array
    {
        return [];
    }

    public function getApiPassword(): string
    {
        return '';
    }

    public function getApiKey(): string
    {
        return '';
    }

    public function getSecurityCode(): string
    {
        return '';
    }

    public function getMerchantId(): int
    {
        return '';
    }

    public function checkBlikLevel0Settings(): bool
    {
        return false;
    }

    public function getBlikLevelZeroStatus(): bool
    {
        return false;
    }

    public function onlyOnlineChannels(): bool
    {
        return false;
    }

    public function redirectToChannel(): bool
    {
        return true;
    }

    public function getPaymentRedirectUrl(): string
    {
        return '';
    }

    public function getTermsURL(): string
    {
        return '';
    }

    public function getInvoiceSendMail(): string
    {
        return '';
    }

    public function getCheckProxy(): bool
    {
        return '';
    }

    public function getCheckTpayIP(): bool
    {
        return true;
    }

    public function getInstallmentsAmountValid(): bool
    {
        return false;
    }

    public function useSandboxMode(): bool
    {
        return true;
    }

    public function getClientId(): string
    {
        return '';
    }

    public function getOpenApiPassword(): string
    {
        return '';
    }

    public function getOpenApiSecurityCode(): ?string
    {
        return '';
    }

    public function getCardApiKey(): ?string
    {
        return '';
    }

    public function getCardApiPassword(): ?string
    {
        return '';
    }

    public function getCardSaveEnabled(): bool
    {
        return '';
    }

    public function getCheckoutCustomerId(): ?string
    {
        return '';
    }

    public function getRSAKey(): string
    {
        return '';
    }

    public function isCustomerLoggedIn(): bool
    {
        return '';
    }

    public function getHashType(): string
    {
        return '';
    }

    public function getVerificationCode(): string
    {
        return '';
    }

    public function getCustomerId($orderId)
    {
        return '';
    }

    public function isCustomerGuest($orderId)
    {
        return '';
    }

    public function getOpenApiClientId()
    {
        return '';
    }
}
