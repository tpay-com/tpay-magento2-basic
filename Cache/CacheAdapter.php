<?php

declare(strict_types=1);

namespace Tpay\Magento2\Cache;

use Magento\Framework\App\CacheInterface;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

class CacheAdapter implements PsrCacheInterface
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->load($key);
    }

    public function set(string $key, mixed $value, $ttl = null): bool
    {
        return $this->cache->save($value, $key, [], $ttl ?: 7200);
    }

    public function delete(string $key): bool
    {
        return $this->cache->remove($key);
    }

    public function clear(): bool
    {
        return $this->cache->clean();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $elements = [];
        foreach ($keys as $key) {
            $elements[] = $this->get($key, $default);
        }

        return $elements;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (false === $this->set($key, $value, $ttl)) {
                return false;
            }
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (false === $this->delete($key)) {
                return false;
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }
}
