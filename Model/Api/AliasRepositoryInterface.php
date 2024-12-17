<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model\Api;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Tpay\Magento2\Model\Api\Data\AliasInterface;

interface AliasRepositoryInterface
{
    /** @throws AlreadyExistsException */
    public function save(AliasInterface $alias): void;

    public function findByCustomerId(int $customerId): ?AliasInterface;

    /** @throws Exception */
    public function remove(AliasInterface $alias): void;
}
