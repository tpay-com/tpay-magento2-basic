<?php

declare(strict_types=1);

namespace Tpay\Magento2\Helper;

class IpValidator
{
    public function isPrivate(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    public function isPublic(?string $ip): bool
    {
        return !$this->isPrivate($ip);
    }
}