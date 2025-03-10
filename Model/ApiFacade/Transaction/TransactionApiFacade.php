<?php

namespace Tpay\Magento2\Model\ApiFacade\Transaction;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Model\ApiFacade\OpenApi;
use Tpay\Magento2\Model\ApiFacade\Transaction\Dto\Channel;
use Tpay\OpenApi\Utilities\TpayException;

class TransactionApiFacade
{
    private const CACHE_LIFETIME = 86400;

    /** @var TransactionOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var CacheInterface */
    private $cache;

    /** @var bool */
    private $useOpenApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(TransactionOriginApi $originApi, OpenApi $openApi, ScopeConfigInterface $storeConfig, CacheInterface $cache, StoreManagerInterface $storeManager)
    {
        $this->originApi = $originApi;
        $this->openApi = $openApi;
        $this->cache = $cache;
        $this->storeManager = $storeManager;
        $this->useOpenApi = $storeConfig->isSetFlag('payment/tpaycom_magento2basic/openapi_settings/open_api_active', ScopeInterface::SCOPE_STORE);
    }

    public function isOpenApiUse(): bool
    {
        return $this->useOpenApi;
    }

    public function create(array $config): array
    {
        return $this->getCurrentApi()->create($config);
    }

    public function createTransaction(array $config): array
    {
        return $this->getCurrentApi()->createTransaction($config);
    }

    public function createWithInstantRedirection(array $config): array
    {
        if (!$this->useOpenApi) {
            throw new TpayException('OpenAPI not availabile - Failed to create transaction with instant redirection');
        }

        return $this->openApi->createWithInstantRedirect($config);
    }

    public function blik($blikTransactionId, $blikCode, $blikAlias): array
    {
        $blikData = [
            'blikToken' => $blikCode,
            'aliases' => $blikAlias ? ['type' => 'UID', 'value' => $blikAlias, 'label' => 'tpay-magento2'] : [],
        ];

        return $this->getCurrentApi()->blik($blikTransactionId, $blikData);
    }

    public function blikAlias($blikAliasTransactionId, $blikAlias): array
    {
        return $this->getCurrentApi()->blikAlias($blikAliasTransactionId, $blikAlias);
    }

    /** @return list<Channel> */
    public function channels(): array
    {
        if (!$this->useOpenApi) {
            return [];
        }

        $cacheKey = 'tpay_channels_' .$this->storeManager->getStore()->getCode();

        $channels = $this->cache->load($cacheKey);

        if ($channels) {
            return unserialize($channels);
        }

        $channels = array_filter($this->openApi->channels(), function (Channel $channel) {
            return true === $channel->available;
        });

        $this->cache->save(serialize($channels), $cacheKey, [Config::CACHE_TAG], self::CACHE_LIFETIME);

        return $channels;
    }

    public function translateGroupToChannel(array $data, bool $redirectToChannel): array
    {
        if ($redirectToChannel && $this->useOpenApi && $data['group'] && !$data['channel'] && TransactionOriginApi::BLIK_CHANNEL != (int)$data['group']) {
            foreach ($this->openApi->channels() as $channel) {
                $group = $channel->groups[0] ?? null;
                if (isset($group['id']) && $group['id'] == $data['group']) {
                    $data['channel'] = $channel->id;
                    $data['group'] = null;

                    return $data;
                }
            }
        }

        return $data;
    }

    public function originApiFieldCorrect(array $data): array
    {
        if (!$this->isOpenApiUse()) {
            unset($data['channel']);
            unset($data['currency']);
            unset($data['language']);
            unset($data['tax_id']);
        }

        return $data;
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }
}
