<?php

declare(strict_types=1);

namespace Tpay\Magento2\Validator;

use Tpay\Magento2\Api\TpayInterface;

class AdditionalPaymentInfoValidator
{
    public function validateCardData(array $data): bool
    {
        return !empty($data[TpayInterface::CARDDATA]) || !empty($data[TpayInterface::CARD_ID]);
    }

    public function validateBlikIfPresent(array $data): bool
    {
        return 6 === strlen($data[TpayInterface::BLIK_CODE]);
    }

    public function validatePresenceOfGroupOrChannel(array $data): bool
    {
        return empty(array_intersect(array_keys($data), [TpayInterface::GROUP, TpayInterface::CHANNEL]));
    }
}
