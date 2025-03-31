<?php

namespace Tpay\Magento2\Service;

use Magento\Framework\App\ResourceConnection;
use Tpay\Magento2\Api\TokenRepositoryInterface;
use Tpay\Magento2\Model\ResourceModel\Token\Collection;
use Zend_Db_Expr;

class TpayTokensService
{
    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var Collection */
    private $collection;

    /** @var TokenRepositoryInterface */
    private $tokenRepository;

    public function __construct(ResourceConnection $resourceConnection, Collection $collection, TokenRepositoryInterface $tokenRepository)
    {
        $this->resourceConnection = $resourceConnection;
        $this->tokenRepository = $tokenRepository;
        $this->collection = $collection;
    }

    public function setCustomerToken(string $customerId, ?string $token, string $shortCode, string $vendor, ?string $crc = null)
    {
        $tokenEntity = $this->tokenRepository->getByToken($token);

        if (!$tokenEntity->getId()) {
            $tokenEntity
                ->setCustomerId($customerId)
                ->setToken($token)
                ->setShortCode($shortCode)
                ->setVendor($vendor)
                ->setCreationTime()
                ->setCrc($crc);

            $this->tokenRepository->save($tokenEntity);
        }
    }

    public function getCustomerTokens(string $customerId, bool $crcRequired = false): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('tpay_credit_cards');

        $select = $connection->select()
            ->from($tableName)
            ->where('cli_id = ?', $customerId)
            ->where(new Zend_Db_Expr('cli_auth IS NOT NULL'))
            ->where(new Zend_Db_Expr($crcRequired ? 'LENGTH(crc) > 1' : 'LENGTH(crc) < 1'));

        $results = [];
        foreach ($connection->fetchAll($select) as $token) {
            $results[] = [
                'tokenId' => $token['id'],
                'token' => $token['cli_auth'],
                'cardShortCode' => $token['short_code'],
                'vendor' => $token['vendor'],
                'crc' => $token['crc'],
            ];
        }

        return $results;
    }

    public function deleteCustomerToken(string $token): bool
    {
        $token = $this->tokenRepository->getByToken($token);

        return $this->tokenRepository->delete($token);
    }

    public function getWithoutAuthCustomerTokens(string $customerId, string $crc): array
    {
        foreach ($this->getToken($customerId) as $token) {
            if (empty($token['crc'])) {
                continue;
            }
            if ($token['crc'] === $crc && empty($token['token'])) {
                return $token;
            }
        }

        return [];
    }

    public function updateTokenById(int $tokenId, string $tokenValue)
    {
        $token = $this->tokenRepository->getById($tokenId);
        $token->setToken($tokenValue);
        $this->tokenRepository->save($token);
    }

    public function getTokenById(int $tokenId, string $customerId, bool $crcRequired = true): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('tpay_credit_cards');

        $select = $connection->select()
            ->from($tableName)
            ->where('id = ?', $tokenId)
            ->where('cli_id = ?', $customerId)
            ->where(new Zend_Db_Expr($crcRequired ? 'LENGTH(crc) > 1' : 'LENGTH(crc) < 1'));

        $result = $connection->fetchAll($select);

        return !empty($result) ? $result[0] : null;
    }

    public function getToken(string $customerId): array
    {
        $tokenCollection = $this->collection;
        $tokenCollection->addFieldToFilter('cli_id', $customerId);

        $results = [];
        foreach ($tokenCollection->getItems() as $token) {
            $results[] = [
                'tokenId' => $token->getId(),
                'token' => $token->getCliAuth(),
                'cardShortCode' => $token->getShortCode(),
                'vendor' => $token->getVendor(),
                'crc' => $token->getCrc(),
            ];
        }

        return $results;
    }
}
