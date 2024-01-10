<?php

namespace tpaycom\magento2basic\Model;

use Magento\Quote\Model\Quote\Payment;
use tpaycom\magento2basic\Api\TpayInterface;

class GenericPaymentPlugin
{
    public function beforeImportData(Payment $compiled, array $data): array
    {
        if (str_contains($data['method'], 'generic')) {
            $data['channel'] = explode('-', $data['method'])[1];
            $data['method'] = TpayInterface::CODE;
        }
        if ('tpaycom_magento2basic_cards' == $data['method']) {
            $data['method'] = TpayInterface::CODE;
        }

        return [$data];
    }
}
