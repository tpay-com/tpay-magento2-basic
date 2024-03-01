<?php

namespace TpayCom\Magento2Basic\Model\Api;

use TpayCom\Magento2Basic\Model\Api\Data\TokensInterface;

interface TokenRepositoryInterface
{
    public function getById(int $tokenId): TokensInterface;

    public function getByToken(string $tokenValue): TokensInterface;

    public function save(TokensInterface $token): TokensInterface;

    public function delete(TokensInterface $token): bool;
}
