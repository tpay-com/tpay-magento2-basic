<?php

namespace Tpay\Magento2\Model;

use Magento\Framework\App\CacheInterface;
use Tpay\OpenApi\Model\Fields\ApiCredentials\Scope;
use Tpay\OpenApi\Model\Fields\Token\AccessToken;
use Tpay\OpenApi\Model\Fields\Token\ExpiresIn;
use Tpay\OpenApi\Model\Fields\Token\IssuedAt;
use Tpay\OpenApi\Model\Fields\Token\TokenType;
use Tpay\OpenApi\Model\Identifiers\ClientId;
use Tpay\OpenApi\Model\Objects\Authorization\Token;
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
        $serialize = $this->serialize($value);
        $this->cache->save($serialize, $key, [TpayConfigProvider::CACHE_TAG], $ttl);
    }

    public function get($key)
    {
        $json = $this->cache->load($key);

        return $this->unserialize($json);
    }

    public function delete($key)
    {
        $this->cache->remove($key);
    }

    public function serialize($value): string
    {
        if ($value instanceof Token) {
            // We have to use serialize
            // phpcs:ignore Magento2.Security.InsecureFunction
            return serialize($value);
        }

        return json_encode($value);
    }

    public function unserialize(string $json)
    {
        $data = json_decode($json, true);
        if (null !== $data) {
            return $data;
        }

        // We have to use serialize
        // phpcs:ignore Magento2.Security.InsecureFunction
        return unserialize(
            $json,
            ['allowed_classes' => [
                Token::class,
                IssuedAt::class,
                Scope::class,
                ExpiresIn::class,
                TokenType::class,
                ClientId::class,
                AccessToken::class,
            ],
            ]
        );
    }
}
