<?php

namespace tpaycom\magento2basic\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use tpaycom\magento2basic\Model\Tokens;

class TpayTokensService extends Tokens
{
    /** @var ResourceConnection */
    private $resourceConnection;

    public function __construct(Context $context, Registry $registry, ResourceConnection $resourceConnection, $resource = null, AbstractDb $resourceCollection = null, array $data = [])
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function setCustomerToken(string $customerId, ?string $token, string $shortCode, string $vendor, ?string $crc = null)
    {
        $tokenEntity = $this->load($token, 'cli_auth');

        if (!$tokenEntity->getId()) {
            $this->setCustomerId($customerId)
                ->setToken($token)
                ->setShortCode($shortCode)
                ->setVendor($vendor)
                ->setCreationTime()
                ->setCrc($crc)
                ->save();
        }
    }

    public function getCustomerTokens(string $customerId, bool $crcRequired = false): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('tpay_credit_cards');

        $select = $connection->select()
            ->from($tableName)
            ->where('cli_id = ?', $customerId)
            ->where(new \Zend_Db_Expr('cli_auth IS NOT NULL'))
            ->where(new \Zend_Db_Expr($crcRequired ? 'crc IS NOT NULL' : 'crc IS NULL'));

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

    public function deleteCustomerToken(string $token): TpayTokensService
    {
        return $this->deleteToken($token)->save();
    }

    public function getWithoutAuthCustomerTokens(int $customerId, string $crc): array
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
        $token = $this->load($tokenId);
        $token->setToken($tokenValue);
        $token->save();
    }

    public function getTokenById(int $tokenId, int $customerId, bool $crcRequired = true): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('tpay_credit_cards');

        $select = $connection->select()
            ->from($tableName)
            ->where('id = ?', $tokenId)
            ->where('cli_id = ?', $customerId)
            ->where(new \Zend_Db_Expr($crcRequired ? 'crc IS NOT NULL' : 'crc IS NULL'));

        $result = $connection->fetchAll($select);

        return !empty($result) ? $result[0] : null;
    }
}
