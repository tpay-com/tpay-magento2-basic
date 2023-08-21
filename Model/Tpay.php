<?php

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
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
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Controller\tpay\Refund;

class Tpay extends AbstractMethod implements TpayInterface
{
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

    /** @var Refund */
    protected $refund;

    /** @var StoreManager */
    protected $storeManager;

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
        Refund $refund,
        Escaper $escaper,
        StoreManager $storeManager,
        $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->refund = $refund;
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

    public function getRedirectURL()
    {
        return $this->redirectURL;
    }

    public function checkBlikLevel0Settings()
    {
        if (!$this->getBlikLevelZeroStatus() || !$this->checkBlikAmount()) {
            return false;
        }

        $apiKey = $this->getApiKey();

        $apiPassword = $this->getApiPassword();

        return !(empty($apiKey) || strlen($apiKey) < 8 || empty($apiPassword) || strlen($apiPassword) < 4);
    }

    /** @return bool */
    public function getInstallmentsAmountValid()
    {
        $amount = $this->getCheckoutTotal();

        return $amount > 300 && $amount < 9259;
    }

    public function getBlikLevelZeroStatus()
    {
        return (bool) $this->getConfigData('blik_level_zero');
    }

    public function getApiKey()
    {
        return $this->getConfigData('api_key_tpay');
    }

    public function getApiPassword()
    {
        return $this->getConfigData('api_password');
    }

    public function getInvoiceSendMail()
    {
        return $this->getConfigData('send_invoice_email');
    }

    public function getTermsURL()
    {
        return $this->termsURL;
    }

    public function getTpayFormData($orderId = null)
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress();
        $amount = number_format($order->getGrandTotal(), 2, '.', '');
        $crc = base64_encode($orderId);
        $name = $billingAddress->getData('firstname').' '.$billingAddress->getData('lastname');

        /** @var string $phone */
        $phone = $billingAddress->getData('telephone');

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
        ];
    }

    public function getMerchantId()
    {
        return (int) $this->getConfigData('merchant_id');
    }

    public function getSecurityCode()
    {
        return $this->getConfigData('security_code');
    }

    public function onlyOnlineChannels()
    {
        return (bool) $this->getConfigData('show_payment_channels_online');
    }

    public function redirectToChannel()
    {
        return (bool) $this->getConfigData('redirect_directly_to_channel');
    }

    public function getCheckProxy()
    {
        return (bool) $this->getConfigData('check_proxy');
    }

    public function getCheckTpayIP()
    {
        return (bool) $this->getConfigData('check_server');
    }

    public function getPaymentRedirectUrl()
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time().uniqid(true)]);
    }

    /**
     * {@inheritDoc}
     *
     * Check that tpay.com payments should be available.
     */
    public function isAvailable(CartInterface $quote = null)
    {
        /** @var float|int $minAmount */
        $minAmount = $this->getConfigData('min_order_total');

        /** @var float|int $maxAmount */
        $maxAmount = $this->getConfigData('max_order_total');

        if (
            $quote
            && ($quote->getBaseGrandTotal() < $minAmount || ($maxAmount && $quote->getBaseGrandTotal() > $maxAmount))
        ) {
            return false;
        }

        if (!$this->getMerchantId()
            || ($quote && !$this->isAvailableForCurrency($quote->getCurrency()->getQuoteCurrencyCode()))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function assignData(DataObject $data)
    {
        /** @var array<string> $additionalData */
        $additionalData = $data->getData('additional_data');

        $info = $this->getInfoInstance();

        $info->setAdditionalInformation(
            static::CHANNEL,
            isset($additionalData[static::CHANNEL]) ? $additionalData[static::CHANNEL] : ''
        );

        $info->setAdditionalInformation(
            static::BLIK_CODE,
            isset($additionalData[static::BLIK_CODE]) ? $additionalData[static::BLIK_CODE] : ''
        );

        if (isset($additionalData[static::TERMS_ACCEPT]) && 1 === $additionalData[static::TERMS_ACCEPT]) {
            $info->setAdditionalInformation(
                static::TERMS_ACCEPT,
                1
            );
        }

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
        $this->refund
            ->setApiKey($this->getApiKey())
            ->setApiPassword($this->getApiPassword())
            ->setMerchantId($this->getMerchantId())
            ->setMerchantSecret($this->getSecurityCode());
        $refundResult = $this->refund->makeRefund($payment, $amount);
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

    public function getConfigData($field, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * Check that the  BLIK should be available for order/quote amount
     *
     * @return bool
     */
    protected function checkBlikAmount()
    {
        return (bool) ($this->getCheckoutTotal() >= $this->minAmountBlik);
    }

    /** @return float current cart total */
    protected function getCheckoutTotal()
    {
        $amount = $this->getCheckout()->getQuote()->getBaseGrandTotal();

        if (!$amount) {
            /** @var int $orderId */
            $orderId = $this->getCheckout()->getLastRealOrderId();

            $order = $this->orderRepository->getByIncrementId($orderId);
            $amount = $order->getGrandTotal();
        }

        return number_format($amount, 2, '.', '');
    }

    /** @return Session */
    protected function getCheckout()
    {
        return $this->checkoutSession;
    }

    /**
     * @param int $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrder($orderId = null)
    {
        if (null === $orderId) {
            /** @var int $orderId */
            $orderId = $this->getCheckout()->getLastRealOrderId();
        }

        return $this->orderRepository->getByIncrementId($orderId);
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    protected function isAvailableForCurrency($currencyCode)
    {
        return !(!in_array($currencyCode, $this->availableCurrencyCodes));
    }

    private function getMagentoVersion()
    {
        $objectManager = ObjectManager::getInstance();

        /** @var ProductMetadataInterface $productMetadata */
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');

        return $productMetadata->getVersion();
    }
}
