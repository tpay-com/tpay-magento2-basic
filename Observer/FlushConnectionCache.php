<?php

namespace Tpay\Magento2\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Tpay\Magento2\Model\TpayConfigProvider;

class FlushConnectionCache implements ObserverInterface
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * Flush Tpay connection cache upon settings' change
     */
    public function execute(EventObserver $observer)
    {
        $configData = $observer->getData('configData');
        $configSection = $configData['section'];
        if ('payment' === $configSection) {
            $this->cache->clean([TpayConfigProvider::CACHE_TAG]);
        }
    }
}
