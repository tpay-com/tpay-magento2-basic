<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Exception;

interface TpayAliasServiceInterface
{
    public function getCustomerAlias(int $customerId);

    public function saveCustomerAlias(int $customerId, string $alias): void;

    /** @throws Exception */
    public function removeCustomerAlias(int $customerId, string $alias): void;
}
