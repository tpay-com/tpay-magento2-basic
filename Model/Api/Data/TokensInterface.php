<?php

namespace tpaycom\magento2basic\Model\Api\Data;

interface TokensInterface
{
    public function setCustomerId($id);

    public function getToken($customerId);

    public function setToken($token);

    public function setShortCode($shortCode);

    public function setCreationTime();

    public function setCrc($crc);
}
