<?php

namespace TpayCom\Magento2Basic\Model;

use Magento\Quote\Model\Quote\Payment;
use TpayCom\Magento2Basic\Api\TpayInterface;

class GenericPaymentPlugin
{
    public function beforeImportData(Payment $compiled, array $data): array
    {
        if ('generic' === substr($data['method'], 0, 7)) {
            $data['channel'] = explode('-', $data['method'])[1];
            $data['method'] = TpayInterface::CODE;
        }

        if ('TpayCom_Magento2Basic_Cards' == $data['method']) {
            $data['method'] = TpayInterface::CODE;
        }

        return [$data];
    }
}
