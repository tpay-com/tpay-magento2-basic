<?php

namespace TpayCom\Magento2Basic\Model;

use Exception;
use TpayCom\Magento2Basic\Model\Api\Data\TokensInterface;
use TpayCom\Magento2Basic\Model\Api\TokenRepositoryInterface;
use TpayCom\Magento2Basic\Model\ResourceModel\Token as TokenResourceModel;

/** @throws Exception */
class TokenRepository implements TokenRepositoryInterface
{
    private $tokenFactory;
    private $tokenResourceModel;

    public function __construct(TokenFactory $tokenFactory, TokenResourceModel $tokenResourceModel)
    {
        $this->tokenResourceModel = $tokenResourceModel;
        $this->tokenFactory = $tokenFactory;
    }

    public function getById(int $tokenId): TokensInterface
    {
        $token = $this->tokenFactory->create();
        $this->tokenResourceModel->load($token, $tokenId);

        return $token;
    }

    public function getByToken(string $tokenValue): TokensInterface
    {
        $token = $this->tokenFactory->create();
        $this->tokenResourceModel->load($token, $tokenValue, 'cli_auth');

        return $token;
    }

    public function save(TokensInterface $token): TokensInterface
    {
        $this->tokenResourceModel->save($token);

        return $token;
    }

    public function delete(TokensInterface $token): bool
    {
        $this->tokenResourceModel->delete($token);

        return true;
    }
}
