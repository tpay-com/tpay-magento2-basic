<?php

namespace tpaycom\magento2basic\Model\Api;

use tpaycom\magento2basic\Model\Api\Data\TokensInterface;

interface TokenRepositoryInterface
{
    public function getById(int $tokenId): TokensInterface;

    public function getByToken(string $tokenValue): TokensInterface;

    public function save(TokensInterface $token): TokensInterface;

    public function delete(TokensInterface $token): bool;
}
