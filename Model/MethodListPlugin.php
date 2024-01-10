<?php

namespace tpaycom\magento2basic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\Config\Source\OnsiteChannels;

class MethodListPlugin
{
    private const CONFIG_PATH = 'payment/tpaycom_magento2basic/onsite_channels';

    /** @var Data */
    private $data;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var OnsiteChannels */
    private $onsiteChannels;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(Data $data, ScopeConfigInterface $scopeConfig, OnsiteChannels $onsiteChannels, StoreManagerInterface $storeManager)
    {
        $this->data = $data;
        $this->scopeConfig = $scopeConfig;
        $this->onsiteChannels = $onsiteChannels;
        $this->storeManager = $storeManager;
    }

    public function afterGetAvailableMethods(MethodList $compiled, $result)
    {
        $onsiteChannels = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $channels = $onsiteChannels ? explode(',', $onsiteChannels) : [];

        $result[] = $this->getMethodInstance('tpay.com - Płatność kartą', 'tpaycom_magento2basic_cards');
        $result = $this->filterResult($result);

        foreach ($channels as $onsiteChannel) {
            $result[] = $this->getMethodInstance(
                $this->onsiteChannels->getLabelFromValue($onsiteChannel),
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

    private function filterResult(array $result): array
    {
        if ($this->storeManager->getStore()->getCurrentCurrencyCode() === 'PLN') {
            return $result;
        }

        return array_filter($result, function ($method) {
            return $method->getCode() !== 'tpaycom_magento2basic';
        });
    }
}
