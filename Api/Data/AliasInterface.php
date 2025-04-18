<?php

namespace Tpay\Magento2\Api\Data;

interface AliasInterface
{
    public function setCustomerId(int $id): self;

    public function setAlias(string $alias): self;

    public function created(): self;
}
