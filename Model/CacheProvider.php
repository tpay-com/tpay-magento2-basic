<?php

namespace Tpay\Magento2\Model;

use Magento\Framework\App\CacheInterface;
use Tpay\OpenApi\Utilities\Cache;

class CacheProvider extends Cache
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function set($key, $value, $ttl)
    {
        $this->cache->save(serialize($value), $key, [TpayConfigProvider::CACHE_TAG], $ttl);
    }

    public function get($key)
    {
        $json = $this->cache->load($key);

        return unserialize($json);
    }

    public function delete($key)
    {
        $this->cache->remove($key);
    }
}
