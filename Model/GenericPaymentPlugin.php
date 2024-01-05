<?php

namespace tpaycom\magento2basic\Model;

use Magento\Quote\Model\Quote\Payment;

class GenericPaymentPlugin
{
    public function beforeImportData(Payment $compiled, array $data)
    {
        if (str_contains($data['method'], 'generic')) {
            $data['channel'] = explode('-', $data['method'])[1];
            $data['method'] = 'generic';
        }

        return [$data];
    }
}
