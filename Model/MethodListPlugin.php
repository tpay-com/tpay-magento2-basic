<?php

namespace tpaycom\magento2basic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
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

    public function __construct(Data $data, ScopeConfigInterface $scopeConfig, OnsiteChannels $onsiteChannels)
    {
        $this->data = $data;
        $this->scopeConfig = $scopeConfig;
        $this->onsiteChannels = $onsiteChannels;
    }

    public function afterGetAvailableMethods(MethodList $compiled, $result)
    {
        $onsiteChannels = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $channels = $onsiteChannels ? explode(',', $onsiteChannels) : [];

        $result[] = $this->getMethodInstance('tpay.com - Płatność kartą', 'tpaycom_magento2basic_cards');

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
}
