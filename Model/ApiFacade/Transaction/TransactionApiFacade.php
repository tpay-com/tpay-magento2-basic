<?php

namespace tpaycom\magento2basic\Model\ApiFacade\Transaction;

use Exception;
use Magento\Framework\App\CacheInterface;
use Tpay\OpenApi\Utilities\TpayException;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\OpenApi;

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

    public function __construct(TpayInterface $tpay, CacheInterface $cache)
    {
        $this->originApi = new TransactionOriginApi($tpay->getApiPassword(), $tpay->getApiKey(), $tpay->getMerchantId(), $tpay->getSecurityCode(), !$tpay->useSandboxMode());
        $this->createOpenApiInstance($tpay->getClientId(), $tpay->getOpenApiPassword(), !$tpay->useSandboxMode());
        $this->cache = $cache;
    }

    public function isOpenApiUse()
    {
        return $this->useOpenApi;
    }

    public function create(array $config)
    {
        return $this->getCurrentApi()->create($config);
    }

    public function createWithInstantRedirection(array $config)
    {
        if (!$this->useOpenApi) {
            throw new TpayException('OpenAPI not availabile - Failed to create transaction with instant redirection');
        }

        return $this->openApi->createWithInstantRedirect($config);
    }

    public function blik($blikTransactionId, $blikCode)
    {
        return $this->originApi->blik($blikTransactionId, $blikCode);
    }

    public function channels(): array
    {
        $channels = $this->cache->load(self::CHANNELS_CACHE_KEY);

        if ($channels) {
            return json_decode($channels, true);
        }

        if (false === $this->useOpenApi) {
            return [];
        }

        $channels = array_filter($this->openApi->channels()['channels'], function (array $channel) {
            return $channel['available'] === true && empty($channel['constraints']) === true;
        });

        $this->cache->save(json_encode($channels), self::CHANNELS_CACHE_KEY, []);

        return $channels;
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOpenApiInstance(string $clientId, string $apiPassword, bool $isProd)
    {
        try {
            $this->openApi = new OpenApi($clientId, $apiPassword, $isProd);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
