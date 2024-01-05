<?php

namespace tpaycom\magento2basic\Model;

use Magento\Framework\Model\AbstractModel;
use tpaycom\magento2basic\Model\Api\Data\TokensInterface;

class Tokens extends AbstractModel implements TokensInterface
{
    /**
     * @param string $id
     *
     * @return $this
     */
    public function setCustomerId($id)
    {
        $this->setData('cli_id', $id);

        return $this;
    }

    public function getToken($customerId)
    {
        // Get tokens collection
        $tokensCollection = $this->getResourceCollection();
        $results = [];
        // Load all data of collection
        foreach ($tokensCollection as $token) {
            if ((int) $token->getCliId() === (int) $customerId) {
                $results[] = [
                    'tokenId' => $token->getId(),
                    'token' => $token->getCliAuth(),
                    'cardShortCode' => $token->getShortCode(),
                    'vendor' => $token->getVendor(),
                    'crc' => $token->getCrc(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->setData('cli_auth', $token);

        return $this;
    }

    /**
     * @param string $shortCode
     *
     * @return $this
     */
    public function setShortCode($shortCode)
    {
        $this->setData('short_code', $shortCode);

        return $this;
    }

    /**
     * @param string $crc
     *
     * @return $this
     */
    public function setCrc($crc)
    {
        $this->setData('crc', $crc);

        return $this;
    }

    /**
     * @param string $vendorName
     *
     * @return $this
     */
    public function setVendor($vendorName)
    {
        $this->setData('vendor', $vendorName);

        return $this;
    }

    /** @return $this */
    public function setCreationTime()
    {
        $this->setData('created_at', date('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @param string $requestToken
     *
     * @return $this
     */
    public function deleteToken($requestToken)
    {
        $tokensCollection = $this->getResourceCollection();
        foreach ($tokensCollection as $token) {
            if ($token->getCliAuth() === $requestToken) {
                $token->delete();
            }
        }

        return $this;
    }

    protected function _construct()
    {
        $this->_init('tpaycom\magento2basic\Model\ResourceModel\Token');
    }
}
