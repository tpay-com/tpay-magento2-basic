<?php

namespace Tpay\Magento2\Model;

use Magento\Quote\Model\Quote\Payment;
use Tpay\Magento2\Api\TpayInterface;

class GenericPaymentPlugin
{
    public function beforeImportData(Payment $compiled, array $data): array
    {
        if ('generic' === substr($data['method'], 0, 7)) {
            $data['channel'] = explode('-', $data['method'])[1];
            $data['method'] = TpayInterface::CODE;
        }

        if ('Tpay_Magento2_Cards' == $data['method']) {
            $data['method'] = TpayInterface::CODE;
        }

        return [$data];
    }
}
