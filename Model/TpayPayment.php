<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Tpay\OriginApi\Validators\FieldsValidator;
use tpaycom\magento2basic\Api\Sales\OrderRepositoryInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Refund\RefundApiFacade;
use tpaycom\magento2basic\Provider\ConfigurationProvider;

class TpayPayment extends Adapter implements TpayInterface
{
    use FieldsValidator;

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $redirectURL = 'https://secure.tpay.com';
    protected $termsURL = 'https://secure.tpay.com/regulamin.pdf';

    /** @var float */
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

    /** @var TpayConfigProvider */
    protected $configurationProvider;

    /** @var PaymentInterface */
    protected $infoInstance;

    /** @var LoggerInterface */
    protected $logger;

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
        UrlInterface              $urlBuilder,
        Session                   $checkoutSession,
        OrderRepositoryInterface  $orderRepository,
        Escaper                   $escaper,
        StoreManager              $storeManager,
        ConfigurationProvider     $configurationProvider,
        PaymentInterface          $infoInstance,
        ManagerInterface          $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory  $paymentDataObjectFactory,
        string                    $code,
        string                    $formBlockType,
        string                    $infoBlockType,
        CommandPoolInterface      $commandPool = null,
        ValidatorPoolInterface    $validatorPool = null,
        CommandManagerInterface   $commandExecutor = null,
        LoggerInterface           $logger = null
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->configurationProvider = $configurationProvider;
        $this->infoInstance = $infoInstance;
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
    }

    public function getPaymentRedirectUrl(): string
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time() . uniqid('', true)]);
    }

    public function setCode(string $code)
    {
        $this->_code = $code;
    }

    public function setTitle(string $title): void
    {
        $this->_title = $title;
    }

    public function checkBlikLevel0Settings(): bool
    {
        return $this->configurationProvider->getBlikLevelZeroStatus() && $this->checkBlikAmount();
    }

    public function getTermsURL(): string
    {
        return $this->termsURL;
    }

    public function getTpayFormData(?string $orderId = null): array
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress();
        $amount = number_format((float)$order->getGrandTotal(), 2, '.', '');
        $crc = base64_encode($orderId);
        $name = $billingAddress->getData('firstname') . ' ' . $billingAddress->getData('lastname');
        $phone = $billingAddress->getData('telephone');

        $om = ObjectManager::getInstance();
        $resolver = $om->get('Magento\Framework\Locale\Resolver');
        $language = $this->validateCardLanguage($resolver->getLocale());

        return [
            'email' => $this->escaper->escapeHtml($order->getCustomerEmail()),
            'name' => $this->escaper->escapeHtml($name),
            'amount' => $amount,
            'description' => 'Zamówienie ' . $orderId,
            'crc' => $crc,
            'address' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('street')),
            'city' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('city')),
            'zip' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('postcode')),
            'country' => $this->escaper->escapeHtml($order->getBillingAddress()->getData('country_id')),
            'return_error_url' => $this->urlBuilder->getUrl('magento2basic/tpay/error'),
            'result_url' => $this->urlBuilder->getUrl('magento2basic/tpay/notification'),
            'return_url' => $this->urlBuilder->getUrl('magento2basic/tpay/success'),
            'phone' => $phone,
            'online' => $this->configurationProvider->onlyOnlineChannels() ? 1 : 0,
            'module' => 'Magento ' . $this->getMagentoVersion(),
            'currency' => $this->getISOCurrencyCode($order->getOrderCurrencyCode()),
            'language' => $language,
        ];
    }

    /**
     * {@inheritDoc}
     * Check that tpay.com payments should be available.
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        $minAmount = $this->configurationProvider->getMinOrderTotal();
        $maxAmount = $this->configurationProvider->getMaxOrderTotal();

        if ($quote && ($quote->getBaseGrandTotal() < $minAmount || ($maxAmount && $quote->getBaseGrandTotal() > $maxAmount))) {
            return false;
        }

        if (!$this->configurationProvider->getMerchantId()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData('additional_data');

        $info = $this->getInfoInstance();
//        $info = $this->infoInstance;
        $info->setAdditionalInformation(static::GROUP, array_key_exists(static::GROUP, $additionalData) ? $additionalData[static::GROUP] : '');
        $info->setAdditionalInformation(static::BLIK_CODE, array_key_exists(static::BLIK_CODE, $additionalData) ? $additionalData[static::BLIK_CODE] : '');
        $info->setAdditionalInformation('channel', $additionalData['channel'] ?? null);
        $info->setAdditionalInformation(static::TERMS_ACCEPT, isset($additionalData[static::TERMS_ACCEPT]) ? '1' === $additionalData[static::TERMS_ACCEPT] : false);
        $info->setAdditionalInformation(static::CARDDATA, $additionalData[static::CARDDATA] ?? '');
        $info->setAdditionalInformation(static::CARD_VENDOR, isset($additionalData[static::CARD_VENDOR]) && in_array($additionalData[static::CARD_VENDOR], $this->supportedVendors) ? $additionalData[static::CARD_VENDOR] : 'undefined');
        $info->setAdditionalInformation(static::CARD_SAVE, isset($additionalData[static::CARD_SAVE]) ? '1' === $additionalData[static::CARD_SAVE] : false);
        $info->setAdditionalInformation(static::CARD_ID, isset($additionalData[static::CARD_ID]) && is_numeric($additionalData[static::CARD_ID]) ? $additionalData[static::CARD_ID] : false);
        $info->setAdditionalInformation(static::SHORT_CODE, isset($additionalData[static::SHORT_CODE]) && is_numeric($additionalData[static::SHORT_CODE]) ? '****' . $additionalData[static::SHORT_CODE] : false);

        return $this;
    }

    /**
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $refundService = new RefundApiFacade($this->configurationProvider);

        $refundResult = $refundService->makeRefund($payment, (float)$amount);
        try {
            if ($refundResult) {
                $payment
                    ->setTransactionId(Transaction::TYPE_REFUND)
                    ->setParentTransactionId($payment->getParentTransactionId())
                    ->setIsTransactionClosed(1)
                    ->setShouldCloseParentTransaction(1);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new Exception(__('Payment refunding error.'));
        }

        return $this;
    }

    /** @return float current cart total */
    public function getCheckoutTotal()
    {
        $amount = (float)$this->getCheckout()->getQuote()->getBaseGrandTotal();

        if (!$amount) {
            $orderId = $this->getCheckout()->getLastRealOrderId();
            $order = $this->orderRepository->getByIncrementId($orderId);
            $amount = $order->getGrandTotal();
        }

        return $amount;
    }

    public function getCheckoutCustomerId(): ?string
    {
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        return $customerSession->getCustomerId();
    }

    public function isCustomerLoggedIn(): bool
    {
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

        return $customerSession->isLoggedIn();
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function getCustomerId($orderId)
    {
        $order = $this->getOrder($orderId);

        return $order->getCustomerId();
    }

    /**
     * @param string $orderId
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
        $minAmount = $this->configurationProvider->getMinOrderTotal();
        $maxAmount = $this->configurationProvider->getMaxOrderTotal();

        if ($grandTotal && ($grandTotal < $minAmount || ($maxAmount && $grandTotal > $maxAmount))) {
            return false;
        }

        if (!$this->configurationProvider->getMerchantId()) {
            return false;
        }

        return !is_null($grandTotal);
    }

    protected function checkBlikAmount(): bool
    {
        return (bool)($this->getCheckoutTotal() >= $this->minAmountBlik);
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
