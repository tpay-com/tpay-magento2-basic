<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use Magento\Framework\Validator\Exception;
use Magento\Sales\Model\Order\Payment\Transaction;
use tpaycom\magento2basic\Controller\tpay\Refund;

/**
 * Class Tpay
 *
 * @package tpaycom\magento2basic\Model
 */
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
     * @var Refund
     */
    protected $refund;

    /**
     * {@inheritdoc}
     *
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Refund $refund
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
        Refund $refund,
        Escaper $escaper,
        $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->refund = $refund;

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
        if (!$this->getBlikLevelZeroStatus() || !$this->checkBlikAmount()) {
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
     * @return bool
     */
    public function getInstallmentsAmountValid()
    {
        $amount = $this->getCheckoutTotal();

        return $amount > 300 && $amount < 9259;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlikLevelZeroStatus()
    {
        return (bool)$this->getConfigData('blik_level_zero');
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
    public function getInvoiceSendMail()
    {
        return $this->getConfigData('send_invoice_email');
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
        $amount = number_format($order->getGrandTotal(), 2, '.', '');
        $crc = base64_encode($orderId);
        $name = $billingAddress->getData('firstname').' '.$billingAddress->getData('lastname');

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
            'online' => $this->onlyOnlineChannels() ? 1 : 0,
            'module' => 'Magento '.$this->getMagentoVersion(),
        ];
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
    public function getCheckProxy()
    {
        return (bool)$this->getConfigData('check_proxy');
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckTpayIP()
    {
        return (bool)$this->getConfigData('check_server');
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentRedirectUrl()
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time().uniqid(true)]);
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

        if (isset($additionalData[static::TERMS_ACCEPT]) && $additionalData[static::TERMS_ACCEPT] === 1) {
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
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Exception
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

    /**
     * Check that the  BLIK should be available for order/quote amount
     *
     * @return bool
     */
    protected function checkBlikAmount()
    {
        return (bool)($this->getCheckoutTotal() > $this->minAmountBlik);
    }

    /**
     * @return float current cart total
     */
    protected function getCheckoutTotal()
    {
        $amount = $this->getCheckout()->getQuote()->getBaseGrandTotal();

        if (!$amount) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
            $order = $this->orderRepository->getByIncrementId($orderId);
            $amount = $order->getGrandTotal();
        }

        return number_format($amount, 2, '.', '');
    }

    /**
     * @return Session
     */
    protected function getCheckout()
    {
        return $this->checkoutSession;
    }

    /**
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrder($orderId = null)
    {
        if ($orderId === null) {
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
        if (!in_array($currencyCode, $this->availableCurrencyCodes)) {
            return false;
        }

        return true;
    }

    private function getMagentoVersion()
    {
        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');

        return $productMetadata->getVersion();
    }

}
