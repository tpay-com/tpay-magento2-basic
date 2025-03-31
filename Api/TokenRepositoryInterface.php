<?php

namespace Tpay\Magento2\Api;

use Tpay\Magento2\Api\Data\TokensInterface;

interface TokenRepositoryInterface
{
    public function getById(int $tokenId): TokensInterface;

    public function getByToken(?string $tokenValue): TokensInterface;

    public function save(TokensInterface $token): TokensInterface;

    public function delete(TokensInterface $token): bool;
}
