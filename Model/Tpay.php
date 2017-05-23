<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;

/**
 * Class Tpay
 *
 * @package tpaycom\magento2basic\Model
 */
class Tpay extends AbstractMethod implements TpayInterface
{
    /**#@+
     * Payment configuration
     */
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    /*#@-*/
    
    protected $availableCurrencyCodes = ['PLN'];
    
    protected $redirectURL = 'https://secure.tpay.com';
    protected $termsURL = 'https://secure.tpay.com/regulamin.pdf';
    
    /**
     * Min. order amount for BLIK level 0
     *
     * @var float
     */
    protected $minAmountBlik = 1.01;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * @var Session
     */
    protected $checkoutSession;
    
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    
    /**
     * @var Escaper
     */
    protected $escaper;
    
    /**
     * {@inheritdoc}
     *
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     */
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
        $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        
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
    
    /**
     * {@inheritdoc}
     */
    public function getRedirectURL()
    {
        return $this->redirectURL;
    }
    
    /**
     * {@inheritdoc}
     */
    public function checkBlikLevel0Settings()
    {
        if (!$this->showPaymentChannels() || !$this->getBlikLevelZeroStatus() || !$this->checkBlikAmount()) {
            return false;
        }
        
        $apiKey = $this->getApiKey();
        
        $apiPassword = $this->getApiPassword();
        
        if (empty($apiKey) || strlen($apiKey) < 8 || empty($apiPassword) || strlen($apiPassword) < 4) {
            return false;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function showPaymentChannels()
    {
        return (bool)$this->getConfigData('show_payment_channels');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlikLevelZeroStatus()
    {
        return (bool)$this->getConfigData('blik_level_zero');
    }
    
    /**
     * Check that the  BLIK should be available for order/quote amount
     *
     * @return bool
     */
    protected function checkBlikAmount()
    {
        $amount = $this->getCheckout()->getQuote()->getBaseGrandTotal();
        
        if (!$amount) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
            $order = $this->orderRepository->getByIncrementId($orderId);
            $amount = $order->getGrandTotal();
        }
        $amount = number_format($amount, 2);
        
        return (bool)($amount > $this->minAmountBlik);
    }
    
    /**
     * @return Session
     */
    protected function getCheckout()
    {
        return $this->checkoutSession;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getApiKey()
    {
        return $this->getConfigData('api_key_tpay');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getApiPassword()
    {
        return $this->getConfigData('api_password');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTermsURL()
    {
        return $this->termsURL;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTpayFormData($orderId = null)
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress();
        $amount = number_format($order->getGrandTotal(), 2);
        $merchantId = $this->getMerchantId();
        $securityCode = $this->getSecurityCode();
        $crc = base64_encode($orderId);
        $md5sum = md5($merchantId . $amount . $crc . $securityCode);
        $name = $billingAddress->getData('firstname') . ' ' . $billingAddress->getData('lastname');
        return [
            'id'           => $merchantId,
            'email'        => $this->escaper->escapeHtml($order->getCustomerEmail()),
            'nazwisko'     => $this->escaper->escapeHtml($name),
            'kwota'        => $amount,
            'opis'         => 'Zamówienie ' . $orderId,
            'md5sum'       => $md5sum,
            'crc'          => $crc,
            'adres'        => $this->escaper->escapeHtml($order->getBillingAddress()->getData('street')),
            'miasto'       => $this->escaper->escapeHtml($order->getBillingAddress()->getData('city')),
            'kod'          => $this->escaper->escapeHtml($order->getBillingAddress()->getData('postcode')),
            'kraj'         => $this->escaper->escapeHtml($order->getBillingAddress()->getData('country_id')),
            'pow_url_blad' => $this->urlBuilder->getUrl('magento2basic/tpay/error'),
            'wyn_url'      => $this->urlBuilder->getUrl('magento2basic/tpay/notification'),
            'pow_url'      => $this->urlBuilder->getUrl('magento2basic/tpay/success'),
            'online'       => $this->onlyOnlineChannels() ? '1' : '0',
            'direct'       => $this->redirectToChannel() ? '1' : '0',
        ];
    }
    
    /**
     * @return Order
     */
    protected function getOrder($orderId = null)
    {
        if ($orderId === null) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
        }
        
        return $this->orderRepository->getByIncrementId($orderId);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMerchantId()
    {
        return (int)$this->getConfigData('merchant_id');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSecurityCode()
    {
        return $this->getConfigData('security_code');
    }
    
    /**
     * {@inheritdoc}
     */
    public function onlyOnlineChannels()
    {
        return (bool)$this->getConfigData('show_payment_channels_online');
    }
    
    /**
     * {@inheritdoc}
     */
    public function redirectToChannel()
    {
        return (bool)$this->getConfigData('redirect_directly_to_channel');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPaymentRedirectUrl()
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time() . uniqid(true)]);
    }
    
    /**
     * {@inheritdoc}
     *
     * Check that tpay.com payments should be available.
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $minAmount = $this->getConfigData('min_order_total');
        $maxAmount = $this->getConfigData('max_order_total');
        
        if ($quote
            && (
                $quote->getBaseGrandTotal() < $minAmount
                || ($maxAmount && $quote->getBaseGrandTotal() > $maxAmount))
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
    
    /**
     * Availability for currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    protected function isAvailableForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->availableCurrencyCodes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function assignData(DataObject $data)
    {
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
        
        $info->setAdditionalInformation(
            static::TERMS_ACCEPT,
            isset($additionalData[static::TERMS_ACCEPT]) ? '1' : ''
        );
        
        return $this;
    }
}
