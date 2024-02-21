<?php

namespace tpaycom\magento2basic\Model;

use tpaycom\magento2basic\Model\Api\Data\TokensInterface;
use tpaycom\magento2basic\Model\Api\TokenRepositoryInterface;
use tpaycom\magento2basic\Model\TokenFactory;
use tpaycom\magento2basic\Model\ResourceModel\Token as TokenResourceModel;

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
