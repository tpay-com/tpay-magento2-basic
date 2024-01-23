<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManager;
use Tpay\OriginApi\Validators\FieldsValidator;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Refund\RefundApiFacade;

class Tpay extends AbstractMethod implements TpayInterface
{
    use FieldsValidator;

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $availableCurrencyCodes = ['PLN'];
    protected $redirectURL = 'https://secure.tpay.com';
    protected $termsURL = 'https://secure.tpay.com/regulamin.pdf';

    /**
     * Min. order amount for BLIK level 0
     *
     * @var float
     */
    protected $minAmountBlik = 0.01;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var Session */
    protected $checkoutSession;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var Escaper */
    protected $escaper;

    /** @var StoreManager */
    protected $storeManager;

    private $supportedVendors = [
        'visa',
        'jcb',
        'dinersclub',
        'maestro',
        'amex',
        'mastercard',
    ];

    /** @var string */
    private $_title;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuilder,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Escaper $escaper,
        StoreManager $storeManager,
        $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
    }

    public function getRedirectURL(): string
    {
        return $this->redirectURL;
    }

    public function setCode(string $code)
    {
        $this->_code = $code;
    }

    public function checkBlikLevel0Settings(): bool
    {
        return $this->getBlikLevelZeroStatus() && $this->checkBlikAmount();
    }

    public function getInstallmentsAmountValid(): bool
    {
        $amount = $this->getCheckoutTotal();

        return $amount > 300 && $amount < 9259;
    }

    public function getBlikLevelZeroStatus(): bool
    {
        return (bool) $this->getConfigData('general_settings/blik_level_zero');
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
        return $this->termsURL;
    }

    public function getOpenApiPassword(): ?string
    {
        return $this->getConfigData('openapi_settings/open_api_password');
    }

    public function getTpayFormData(?string $orderId = null): array
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress();
        $amount = number_format((float) $order->getGrandTotal(), 2, '.', '');
        $crc = base64_encode($orderId);
        $name = $billingAddress->getData('firstname').' '.$billingAddress->getData('lastname');
        $phone = $billingAddress->getData('telephone');

        $om = ObjectManager::getInstance();
        $resolver = $om->get('Magento\Framework\Locale\Resolver');
        $language = $this->validateCardLanguage($resolver->getLocale());

        return [
            'email' => $this->escaper->escapeHtml($order->getCustomerEmail()),
            'name' => $this->escaper->escapeHtml($name),
            'amount' => $amount,
            'description' => 'Zamówienie '.$orderId,
            'crc' => $crc,
            'address' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('street')),
            'city' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('city')),
            'zip' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('postcode')),
            'country' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('country_id')),
            'return_error_url' => $this->urlBuilder->getUrl('magento2basic/tpay/error'),
            'result_url' => $this->urlBuilder->getUrl('magento2basic/tpay/notification'),
            'return_url' => $this->urlBuilder->getUrl('magento2basic/tpay/success'),
            'phone' => $phone,
            'online' => $this->onlyOnlineChannels() ? 1 : 0,
            'module' => 'Magento '.$this->getMagentoVersion(),
            'currency' => $this->getISOCurrencyCode($order->getOrderCurrencyCode()),
            'language' => $language,
        ];
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

    public function setTitle(string $title): void
    {
        $this->_title = $title;
    }

    public function getTitle(): string
    {
        return $this->_title ?? $this->getConfigData('general_settings/title');
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

    public function getPaymentRedirectUrl(): string
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time().uniqid('', true)]);
    }

    /**
     * {@inheritDoc}
     * Check that tpay.com payments should be available.
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        $minAmount = $this->getConfigData('sale_settings/min_order_total');
        $maxAmount = $this->getConfigData('sale_settings/max_order_total');

        if ($quote && ($quote->getBaseGrandTotal() < $minAmount || ($maxAmount && $quote->getBaseGrandTotal() > $maxAmount))) {
            return false;
        }

        if (!$this->getMerchantId()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData('additional_data');
        $info = $this->getInfoInstance();

        $info->setAdditionalInformation(static::GROUP, array_key_exists(static::GROUP, $additionalData) ? $additionalData[static::GROUP] : '');

        $info->setAdditionalInformation(static::BLIK_CODE, array_key_exists(static::BLIK_CODE, $additionalData) ? $additionalData[static::BLIK_CODE] : '');

        $info->setAdditionalInformation('channel', $additionalData['channel'] ?? null);

        $info->setAdditionalInformation(static::TERMS_ACCEPT, isset($additionalData[static::TERMS_ACCEPT]) ? '1' === $additionalData[static::TERMS_ACCEPT] : false);

        $info->setAdditionalInformation(static::CARDDATA, $additionalData[static::CARDDATA] ?? '');
        $info->setAdditionalInformation(static::CARD_VENDOR, isset($additionalData[static::CARD_VENDOR]) && in_array($additionalData[static::CARD_VENDOR], $this->supportedVendors) ? $additionalData[static::CARD_VENDOR] : 'undefined');
        $info->setAdditionalInformation(static::CARD_SAVE, isset($additionalData[static::CARD_SAVE]) ? '1' === $additionalData[static::CARD_SAVE] : false);
        $info->setAdditionalInformation(static::CARD_ID, isset($additionalData[static::CARD_ID]) && is_numeric($additionalData[static::CARD_ID]) ? $additionalData[static::CARD_ID] : false);
        $info->setAdditionalInformation(static::SHORT_CODE, isset($additionalData[static::SHORT_CODE]) && is_numeric($additionalData[static::SHORT_CODE]) ? '****'.$additionalData[static::SHORT_CODE] : false);

        return $this;
    }

    /**
     * Payment refund
     *
     * @param float $amount
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $refundService = new RefundApiFacade($this);

        $refundResult = $refundService->makeRefund($payment, (float) $amount);
        try {
            if ($refundResult) {
                $payment
                    ->setTransactionId(Transaction::TYPE_REFUND)
                    ->setParentTransactionId($payment->getParentTransactionId())
                    ->setIsTransactionClosed(1)
                    ->setShouldCloseParentTransaction(1);
            }
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment refunding error.'));
            throw new Exception(__('Payment refunding error.'));
        }

        return $this;
    }

    /** @return float current cart total */
    public function getCheckoutTotal()
    {
        $amount = (float) $this->getCheckout()->getQuote()->getBaseGrandTotal();

        if (!$amount) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
            $order = $this->orderRepository->getByIncrementId($orderId);
            $amount = $order->getGrandTotal();
        }

        return $amount;
    }

    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return parent::getConfigData($field, $storeId);
    }

    public function getCardSaveEnabled(): bool
    {
        return false;
    }

    public function getCheckoutCustomerId(): ?string
    {
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        return $customerSession->getCustomerId();
    }

    public function getRSAKey(): ?string
    {
        return $this->getConfigData('cardpayment_settings/rsa_key');
    }

    public function isCustomerLoggedIn(): bool
    {
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        return $customerSession->isLoggedIn();
    }

    public function getHashType(): string
    {
        return $this->getConfigData('cardpayment_settings/cardpayment_originapi_settings/hash_type');
    }

    public function getVerificationCode(): string
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

    /**
     * @param string $orderId
     *
     * @return string
     */
    public function getCustomerId($orderId)
    {
        $order = $this->getOrder($orderId);

        return $order->getCustomerId();
    }

    /**
     * check if customer was logged while placing order
     *
     * @param string $orderId
     *
     * @return bool
     */
    public function isCustomerGuest($orderId)
    {
        $order = $this->getOrder($orderId);

        return $order->getCustomerIsGuest();
    }

    public function getISOCurrencyCode($orderCurrency)
    {
        return $this->validateCardCurrency($orderCurrency);
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

    /** Check that the  BLIK should be available for order/quote amount */
    protected function checkBlikAmount(): bool
    {
        return (bool) ($this->getCheckoutTotal() >= $this->minAmountBlik);
    }

    protected function getCheckout(): Session
    {
        return $this->checkoutSession;
    }

    protected function getOrder(?string $orderId = null): \Magento\Sales\Api\Data\OrderInterface
    {
        if (null === $orderId) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
        }

        return $this->orderRepository->getByIncrementId($orderId);
    }

    private function getMagentoVersion()
    {
        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');

        return $productMetadata->getVersion();
    }
}
