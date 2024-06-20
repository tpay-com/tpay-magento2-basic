<?php

namespace Tpay\Magento2\Model\ApiFacade\Transaction;

use Exception;
use Magento\Framework\App\CacheInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
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

    /** @var TpayConfigInterface */
    private $tpay;

    /** @var CacheInterface */
    private $cache;

    /** @var bool */
    private $useOpenApi = false;

    public function __construct(TpayConfigInterface $tpay, CacheInterface $cache)
    {
        $this->tpay = $tpay;
        $this->cache = $cache;
    }

    public function isOpenApiUse(): bool
    {
        $this->connectApi();

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

    public function blik($blikTransactionId, $blikCode): array
    {
        return $this->getCurrentApi()->blik($blikTransactionId, $blikCode);
    }

    /** @return list<Channel> */
    public function channels(): array
    {
        $this->connectApi();

        if (!$this->useOpenApi) {
            return [];
        }

        $cacheKey = 'tpay_channels_'.md5(join('|', [$this->tpay->getOpenApiClientId(), $this->tpay->getOpenApiPassword(), !$this->tpay->useSandboxMode()]));

        $channels = $this->cache->load($cacheKey);

        if ($channels) {
            return unserialize($channels);
        }

        $channels = array_filter($this->openApi->channels(), function (Channel $channel) {
            return true === $channel->available;
        });

        $this->cache->save(serialize($channels), $cacheKey, [\Magento\Framework\App\Config::CACHE_TAG], self::CACHE_LIFETIME);

        return $channels;
    }

    public function translateGroupToChannel(array $data, bool $redirectToChannel): array
    {
        if ($redirectToChannel && $this->useOpenApi && $data['group'] && !$data['channel'] && TransactionOriginApi::BLIK_CHANNEL != (int) $data['group']) {
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
        }

        return $data;
    }

    private function getCurrentApi()
    {
        $this->connectApi();

        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function connectApi()
    {
        if (null == $this->openApi && null === $this->originApi) {
            $this->createOriginApiInstance($this->tpay);
            $this->createOpenApiInstance($this->tpay);
        }
    }

    private function createOriginApiInstance(TpayConfigInterface $tpay)
    {
        if (!$tpay->isOriginApiEnabled()) {
            $this->originApi = null;

            return;
        }

        try {
            $this->originApi = new TransactionOriginApi($tpay->getApiPassword(), $tpay->getApiKey(), $tpay->getMerchantId(), $tpay->getSecurityCode(), !$tpay->useSandboxMode());
        } catch (Exception $exception) {
            $this->originApi = null;
        }
    }

    private function createOpenApiInstance(TpayConfigInterface $tpay)
    {
        if (!$tpay->isOpenApiEnabled()) {
            $this->openApi = null;
            $this->useOpenApi = false;

            return;
        }

        try {
            $this->openApi = new OpenApi($tpay, $this->cache);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
