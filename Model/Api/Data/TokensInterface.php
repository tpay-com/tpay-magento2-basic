<?php

namespace TpayCom\Magento2Basic\Model\Api\Data;

interface TokensInterface
{
    public function setCustomerId(string $id);

    public function setToken(string $token);

    public function setShortCode(string $shortCode);

    public function setCreationTime();

    public function setCrc(?string $crc = null);

    public function setVendor(?string $vendor = null);
}
