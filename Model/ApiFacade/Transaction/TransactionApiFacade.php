<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Transaction;

use Exception;
use Magento\Framework\App\CacheInterface;
use Tpay\OpenApi\Utilities\TpayException;
use tpaycom\magento2basic\Api\TpayConfigInterface;
use tpaycom\magento2basic\Model\ApiFacade\OpenApi;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\Dto\Channel;

class TransactionApiFacade
{
    private const CHANNELS_CACHE_KEY = 'tpay_channels';
    private const CACHE_LIFETIME = 86400;

    /** @var TransactionOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    /** @var CacheInterface */
    private $cache;

    public function __construct(TpayConfigInterface $tpay, CacheInterface $cache)
    {
        $this->createOriginApiInstance($tpay);
        $this->createOpenApiInstance($tpay);
        $this->cache = $cache;
    }

    public function isOpenApiUse(): bool
    {
        return $this->useOpenApi;
    }

    public function create(array $config): array
    {
        return $this->getCurrentApi()->create($config);
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
        return $this->originApi->blik($blikTransactionId, $blikCode);
    }

    /** @return list<Channel> */
    public function channels(): array
    {
        $channels = $this->cache->load(self::CHANNELS_CACHE_KEY);

        if ($channels) {
            return unserialize($channels);
        }

        if (false === $this->useOpenApi) {
            return [];
        }

        $channels = array_filter($this->openApi->channels(), function (Channel $channel) {
            return true === $channel->available;
        });

        $this->cache->save(serialize($channels), self::CHANNELS_CACHE_KEY, [], self::CACHE_LIFETIME);

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
        return $this->useOpenApi ? $this->openApi : $this->originApi;
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
            $this->openApi = new OpenApi($tpay);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
