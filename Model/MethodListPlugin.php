<?php

namespace TpayCom\Magento2Basic\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use TpayCom\Magento2Basic\Model\Config\Source\OnsiteChannels;

class MethodListPlugin
{
    private const CONFIG_PATH = 'payment/tpaycom_magento2basic/openapi_settings/onsite_channels';

    /** @var Data */
    private $data;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var OnsiteChannels */
    private $onsiteChannels;

    /** @var StoreManagerInterface */
    private $storeManager;

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
        StoreManagerInterface $storeManager,
        TpayPayment $tpay,
        TpayConfigInterface $tpayConfig,
        Session $checkoutSession,
        TransactionApiFacade $transactions,
        ConstraintValidator $constraintValidator
    ) {
        $this->data = $data;
        $this->scopeConfig = $scopeConfig;
        $this->onsiteChannels = $onsiteChannels;
        $this->storeManager = $storeManager;
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->transactions = $transactions;
        $this->constraintValidator = $constraintValidator;
    }

    public function afterGetAvailableMethods(MethodList $compiled, $result)
    {
        $onsiteChannels = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $channelList = $onsiteChannels ? explode(',', $onsiteChannels) : [];
        $channels = $this->transactions->channels();

        if ($this->constraintValidator->isClientCountryValid($this->tpayConfig->isAllowSpecific(), $this->checkoutSession->getQuote()->getBillingAddress()->getCountryId(), $this->tpayConfig->getSpecificCountry())) {
            return [];
        }

        if (!$this->tpay->isCartValid($this->checkoutSession->getQuote()->getGrandTotal())) {
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
            $result[] = $this->getMethodInstance($this->tpayConfig->getCardTitle(), 'TpayCom_Magento2Basic_Cards');
        }

        return $result;
    }

    private function filterResult(array $result): array
    {
        if ($this->isPlnPayment()) {
            return $result;
        }

        return array_filter($result, function ($method) {
            return 'TpayCom_Magento2Basic' !== $method->getCode();
        });
    }

    private function isPlnPayment(): bool
    {
        return 'PLN' === $this->storeManager->getStore()->getCurrentCurrencyCode();
    }
}
