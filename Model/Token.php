<?php

namespace Tpay\Magento2\Model;

use Magento\Framework\Model\AbstractModel;
use Tpay\Magento2\Model\Api\Data\TokensInterface;

class Token extends AbstractModel implements TokensInterface
{
    public function setCustomerId(string $id): self
    {
        $this->setData('cli_id', $id);

        return $this;
    }

    public function setToken(?string $token = null): self
    {
        $this->setData('cli_auth', $token);

        return $this;
    }

    public function setShortCode(string $shortCode): self
    {
        $this->setData('short_code', $shortCode);

        return $this;
    }

    public function setCreationTime(): self
    {
        $this->setData('created_at', date('Y-m-d H:i:s'));

        return $this;
    }

    public function setCrc(?string $crc = null): self
    {
        $this->setData('crc', $crc);

        return $this;
    }

    public function setVendor(?string $vendor = null): self
    {
        $this->setData('vendor', $vendor);

        return $this;
    }

    protected function _construct()// phpcs:ignore
    {
        $this->_init(ResourceModel\Token::class);
    }
}
