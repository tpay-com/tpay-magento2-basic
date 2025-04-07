<?php

namespace Tpay\Magento2\Model;

use Tpay\Magento2\Api\Data\TokensInterface;
use Tpay\Magento2\Api\TokenRepositoryInterface;
use Tpay\Magento2\Model\ResourceModel\Token as TokenResourceModel;

class TokenRepository implements TokenRepositoryInterface
{
    private $tokenFactory;
    private $tokenResourceModel;

    /** @phpstan-ignore-next-line */
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

    public function getByToken(?string $tokenValue = null): TokensInterface
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
