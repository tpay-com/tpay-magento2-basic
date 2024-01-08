<?php

namespace tpaycom\magento2basic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use tpaycom\magento2basic\Model\Config\Source\OnsiteChannels;

class TpayConfig
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

        $secondTpay = false;
        foreach ($result as $paymentMethod) {
            if ($paymentMethod->getCode() == 'tpaycom_magento2basic') {
                if ($secondTpay) {
                    $paymentMethod->setCode('tpaycom_magento2basic_cards');
                } else {
                    $secondTpay = true;
                }
            }
        }

        if (!$onsiteChannels){
            return $result;
        }

        foreach (explode(',', $onsiteChannels) as $onsiteChannel) {
            $method = $this->data->getMethodInstance('generic');
            $method->setChannelId($onsiteChannel);
            $method->setTitle($this->onsiteChannels->getLabelFromValue($onsiteChannel));
            $method->setCode("generic-".$onsiteChannel);

            $result[] = $method;
        }

        return $result;
    }
}
