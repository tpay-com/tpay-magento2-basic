<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\Resolver;
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
use Tpay\Magento2\Api\Sales\OrderRepositoryInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Refund\RefundApiFacade;
use Tpay\Magento2\Provider\ConfigurationProvider;
use Tpay\OriginApi\Validators\FieldsValidator;

class TpayPayment extends Adapter implements TpayInterface
{
    use FieldsValidator;

    protected $code;
    protected $title;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $redirectURL = 'https://secure.tpay.com';

    /** @var float */
    protected $minAmountBlik = 0.01;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var Session */
    protected $checkoutSession;

    /** @var CustomerSession */
    protected $customerSession;

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

    /** @var Resolver */
    protected $resolver;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CacheInterface */
    protected $cache;

    private $supportedVendors = [
        'visa',
        'jcb',
        'dinersclub',
        'maestro',
        'amex',
        'mastercard',
    ];

    public function __construct(
        UrlInterface $urlBuilder,
        Session $checkoutSession,
        CustomerSession $customerSession,
        OrderRepositoryInterface $orderRepository,
        Escaper $escaper,
        StoreManager $storeManager,
        ConfigurationProvider $configurationProvider,
        PaymentInterface $infoInstance,
        Resolver $resolver,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        string $code,
        string $formBlockType,
        string $infoBlockType,
        CacheInterface $cache,
        ?CommandPoolInterface $commandPool = null,
        ?ValidatorPoolInterface $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface $logger = null
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->configurationProvider = $configurationProvider;
        $this->infoInstance = $infoInstance;
        $this->resolver = $resolver;
        $this->cache = $cache;
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

    public function isActive($storeId = null): bool
    {
        return (bool) $this->configurationProvider->getConfigData('active', $storeId);
    }

    public function getPaymentRedirectUrl(): string
    {
        return $this->urlBuilder->getUrl('magento2basic/tpay/redirect', ['uid' => time().uniqid('', true)]);
    }

    public function setCode(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        if ($this->code) {
            return $this->code;
        }

        return parent::getCode();
    }

    public function getTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return $this->configurationProvider->getTitle() ?? parent::getTitle();
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function checkBlikLevel0Settings(): bool
    {
        return $this->configurationProvider->getBlikLevelZeroStatus() && $this->checkBlikAmount();
    }

    public function getTpayFormData(?string $orderId = null): array
    {
        $order = $this->getOrder($orderId);
        $billingAddress = $order->getBillingAddress();
        $amount = number_format((float) $order->getBaseGrandTotal(), 2, '.', '');
        $crc = base64_encode($orderId);
        $name = $billingAddress->getData('firstname').' '.$billingAddress->getData('lastname');
        $phone = $billingAddress->getData('telephone');
        $language = $this->validateCardLanguage($this->resolver->getLocale());

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
            'online' => $this->configurationProvider->onlyOnlineChannels() ? 1 : 0,
            'module' => 'Magento '.$this->configurationProvider->getMagentoVersion(),
            'currency' => $this->getISOCurrencyCode($order->getBaseCurrencyCode()),
            'language' => $language,
        ];
    }

    public function isAvailable(?CartInterface $quote = null)
    {
        if (!$this->configurationProvider->isTpayActive()) {
            return false;
        }

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
        $info = $this->getInfoInstance();
        $additionalData = array_merge($info->getAdditionalInformation(), $data->getData('additional_data'));
        $info->setAdditionalInformation(static::GROUP, array_key_exists(static::GROUP, $additionalData) ? $additionalData[static::GROUP] : '');
        $info->setAdditionalInformation(static::BLIK_CODE, array_key_exists(static::BLIK_CODE, $additionalData) ? $additionalData[static::BLIK_CODE] : '');
        $info->setAdditionalInformation(static::CHANNEL, $additionalData[static::CHANNEL] ?? null);
        $info->setAdditionalInformation(static::TERMS_ACCEPT, isset($additionalData[static::TERMS_ACCEPT]) ? (bool) ($additionalData[static::TERMS_ACCEPT]) : false);
        $info->setAdditionalInformation(static::CARDDATA, $additionalData[static::CARDDATA] ?? '');
        $info->setAdditionalInformation(static::CARD_VENDOR, isset($additionalData[static::CARD_VENDOR]) && in_array($additionalData[static::CARD_VENDOR], $this->supportedVendors) ? $additionalData[static::CARD_VENDOR] : 'undefined');
        $info->setAdditionalInformation(static::CARD_SAVE, isset($additionalData[static::CARD_SAVE]) ? (bool) ($additionalData[static::CARD_SAVE]) : false);
        $info->setAdditionalInformation(static::CARD_ID, isset($additionalData[static::CARD_ID]) && is_numeric($additionalData[static::CARD_ID]) ? $additionalData[static::CARD_ID] : false);
        $info->setAdditionalInformation(static::SHORT_CODE, isset($additionalData[static::SHORT_CODE]) && is_numeric($additionalData[static::SHORT_CODE]) ? '****'.$additionalData[static::SHORT_CODE] : false);

        return $this;
    }

    /**
     * @param float $amount
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $refundService = new RefundApiFacade($this->configurationProvider, $this->cache);

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
            $this->logger->debug($e->getMessage());
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
            $amount = $order->getBaseGrandTotal();
        }

        return $amount;
    }

    public function getCheckoutCustomerId(): ?string
    {
        return $this->customerSession->getCustomerId();
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
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
}
