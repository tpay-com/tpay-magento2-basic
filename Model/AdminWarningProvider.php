<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Tpay\Magento2\Helper\IpValidator;

class AdminWarningProvider
{
    private $remoteAddress;
    private $ipValidator;

    public function __construct(
        RemoteAddress $remoteAddress,
        IpValidator $ipValidator
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->ipValidator = $ipValidator;
    }

    public function shouldShow(): bool
    {
        $ip = $this->remoteAddress->getRemoteAddress();

        return $this->ipValidator->isPrivate($ip);
    }

    public function getIp(): ?string
    {
        return $this->remoteAddress->getRemoteAddress();
    }
}