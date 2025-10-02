<?php

namespace Tpay\Magento2\ViewModel\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\TpayPayment;
use Tpay\Magento2\Provider\ConfigurationProvider;

class Success implements ArgumentInterface
{
    /** @var Session */
    private $checkoutSession;

    /** @var TransactionApiFacade */
    private $transactionApi;

    /** @var FormKey */
    private $formKey;

    /** @var int */
    private $orderId;

    /** @var string */
    private $paymentId;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    public function __construct(Session $checkoutSession, TransactionApiFacade $transactionApi, FormKey $formKey, ConfigurationProvider $configurationProvider)
    {
        $this->checkoutSession = $checkoutSession;
        $this->transactionApi = $transactionApi;
        $this->formKey = $formKey;
        $this->configurationProvider = $configurationProvider;
    }

    public function getPaymentStatus(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $this->orderId = $order->getId();
        $payment = $order->getPayment();
        if (TpayPayment::CODE !== $payment->getMethod()) {
            return 'non-tpay';
        }
        $paymentId = $payment->getAdditionalInformation('transaction_id');
        if (empty($paymentId)) {
            return 'non-tpay';
        }
        $this->paymentId = $paymentId;
        $status = $this->transactionApi->getStatus($paymentId);

        if (in_array($status['status'], ['paid', 'correct', 'success'])) {
            return 'success';
        }

        if (!empty($status['payments']['attempts'])) {
            return 'failed';
        }

        return 'wait';
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getTransactionId(): string
    {
        return $this->paymentId;
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getTermsUrl(): string
    {
        return $this->configurationProvider->getTermsURL();
    }

    public function getRegulationsUrl(): string
    {
        return $this->configurationProvider->getRegulationsURL();
    }
}
