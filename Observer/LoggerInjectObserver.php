<?php

namespace Tpay\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Tpay\OpenApi\Utilities\Logger as OpenApiLogger;
use Tpay\OriginApi\Utilities\Logger as OriginApiLogger;

/**
 * This is temporary logger injector into Tpay classess.
 * After moving currency card sales into open api and simplification of our API
 * This Observer will disappear
 */
class LoggerInjectObserver implements ObserverInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        OpenApiLogger::setLogger($this->logger);
        OriginApiLogger::setLogger($this->logger);
    }
}
