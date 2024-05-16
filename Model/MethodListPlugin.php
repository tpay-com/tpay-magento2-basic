<?php

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\Config\Source\OnsiteChannels;

class MethodListPlugin
{
    private const CONFIG_PATH = 'payment/tpaycom_magento2basic/openapi_settings/onsite_channels';

    /** @var TpayInterface */
    protected $paymentMethod;

    /** @var Data */
    private $data;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var OnsiteChannels */
    private $onsiteChannels;

    /** @var TpayPayment */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var Session */
    private $checkoutSession;

    /** @var TransactionApiFacade */
    private $transactions;

    /** @var ConstraintValidator */
    private $constraintValidator;

    public function __construct(
        Data $data,
        ScopeConfigInterface $scopeConfig,
        OnsiteChannels $onsiteChannels,
        TpayPayment $tpay,
        TpayConfigInterface $tpayConfig,
        Session $checkoutSession,
        TransactionApiFacade $transactions,
        ConstraintValidator $constraintValidator,
        TpayInterface $paymentMethod
    ) {
        $this->data = $data;
        $this->scopeConfig = $scopeConfig;
        $this->onsiteChannels = $onsiteChannels;
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->transactions = $transactions;
        $this->constraintValidator = $constraintValidator;
        $this->paymentMethod = $paymentMethod;
    }

    public function afterGetAvailableMethods(MethodList $compiled, $result)
    {
        if (!$this->paymentMethod->isAvailable()) {
            return $result;
        }

        $onsiteChannels = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $channelList = $onsiteChannels ? explode(',', $onsiteChannels) : [];
        $channels = $this->transactions->channels();
        $conutryId = $this->checkoutSession->getQuote()->getBillingAddress()->getCountryId();

        if ($conutryId && $this->constraintValidator->isClientCountryValid($this->tpayConfig->isAllowSpecific(), $conutryId, $this->tpayConfig->getSpecificCountry())) {
            return [];
        }

        if (!$this->tpay->isCartValid($this->checkoutSession->getQuote()->getBaseGrandTotal())) {
            return $result;
        }

        $result = $this->addCardMethod($result);
        $result = $this->filterResult($result);

        if (!$this->transactions->isOpenApiUse() || !$this->isPlnPayment()) {
            return $result;
        }

        foreach ($channelList as $onsiteChannel) {
            $channel = $channels[$onsiteChannel];

            if (!empty($channel->constraints) && !$this->constraintValidator->validate($channel->constraints)) {
                continue;
            }

            $title = $this->onsiteChannels->getLabelFromValue($onsiteChannel);
            $result[] = $this->getMethodInstance(
                $title,
                "generic-{$onsiteChannel}"
            );
        }

        return $result;
    }

    public function getMethodInstance(string $title, string $code): MethodInterface
    {
        $method = $this->data->getMethodInstance(TpayInterface::CODE);
        $method->setTitle($title);
        $method->setCode($code);

        return $method;
    }

    private function addCardMethod(array $result): array
    {
        if ($this->tpayConfig->isCardEnabled()) {
            $result[] = $this->getMethodInstance($this->tpayConfig->getCardTitle(), 'Tpay_Magento2_Cards');
        }

        return $result;
    }

    private function filterResult(array $result): array
    {
        if (!$this->tpayConfig->isOpenApiEnabled() && !$this->tpayConfig->isOriginApiEnabled()) {
            return $this->filterTransaction($result);
        }

        if ($this->isPlnPayment()) {
            return $result;
        }

        return $this->filterTransaction($result);
    }

    private function isPlnPayment(): bool
    {
        return 'PLN' === $this->tpayConfig->getPaymentCurrency();
    }

    private function filterTransaction(array $result): array
    {
        return array_filter($result, function ($method) {
            return 'Tpay_Magento2' !== $method->getCode();
        });
    }
}
