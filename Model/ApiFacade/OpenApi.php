<?php

namespace Tpay\Magento2\Model\ApiFacade;

use Magento\Framework\App\CacheInterface;
use Magento\Payment\Model\InfoInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\Dto\Channel;
use Tpay\OpenApi\Api\TpayApi;

class OpenApi
{
    public const AUTH_TOKEN_CACHE_KEY = 'tpay_auth_token_%s';

    /** @var TpayApi */
    private $tpayApi;

    private $cache;

    public function __construct(TpayConfigInterface $tpay, CacheInterface $cache, ?int $storeId = null)
    {
        $this->cache = $cache;
        $this->tpayApi = new TpayApi($tpay->getOpenApiClientId($storeId), $tpay->getOpenApiPassword($storeId), !$tpay->useSandboxMode($storeId));
        $token = $this->cache->load($this->getAuthTokenCacheKey($tpay, $storeId));
        if ($token) {
            $this->tpayApi->setCustomToken(unserialize($token));
        }

        $this->tpayApi->authorization();

        if (!$token) {
            $this->cache->save(serialize($this->tpayApi->getToken()), $this->getAuthTokenCacheKey($tpay, $storeId), [\Magento\Framework\App\Config::CACHE_TAG], 7100);
        }
    }

    public function create(array $data): array
    {
        if (!empty($data['blikPaymentData'])) {
            return $this->createBlikZero($data);
        }

        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->tpayApi->transactions()->createTransaction($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createTransaction(array $data): array
    {
        if (!empty($data['blikPaymentData'])) {
            return $this->createBlikZeroTransaction($data);
        }

        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->tpayApi->transactions()->createTransaction($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createWithInstantRedirect(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->tpayApi->transactions()->createTransactionWithInstantRedirection($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createBlikZeroTransaction(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        unset($transactionData['pay']);

        $transaction = $this->tpayApi->transactions()->createTransactionWithInstantRedirection($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function blik(string $transactionId, string $blikCode): array
    {
        $additional_payment_data = [
            'channelId' => 64,
            'method' => 'pay_by_link',
            'blikPaymentData' => [
                'type' => 0,
                'blikToken' => $blikCode,
            ],
        ];

        $result = $this->tpayApi->transactions()->createInstantPaymentByTransactionId($additional_payment_data, $transactionId);

        return $this->updateRedirectUrl($this->waitForBlikAccept($result));
    }

    public function createBlikZero(array $data): array
    {
        $transaction = $this->createBlikZeroTransaction($data);

        return $this->blik($transaction['transactionId'], $data['blikPaymentData']['blikToken']);
    }

    public function makeRefund(InfoInterface $payment, float $amount): array
    {
        return $this->tpayApi->transactions()->createRefundByTransactionId(
            ['amount' => $amount],
            $payment->getAdditionalInformation('transaction_id')
        );
    }

    public function channels(): array
    {
        $result = $this->tpayApi->transactions()->getChannels();
        $channels = [];

        foreach ($result['channels'] ?? [] as $channel) {
            $channels[$channel['id']] = new Channel(
                $channel['id'],
                $channel['name'],
                $channel['fullName'],
                $channel['image']['url'],
                $channel['available'],
                $channel['onlinePayment'],
                $channel['instantRedirection'],
                $channel['groups'],
                $channel['constraints']
            );
        }

        return $channels;
    }

    private function handleDataStructure(array $data): array
    {
        $paymentData = [
            'amount' => $data['amount'],
            'description' => $data['description'],
            'hiddenDescription' => $data['crc'],
            'lang' => strstr($data['language'], '_', true) ? strstr($data['language'], '_', true) : $data['language'],
            'payer' => [
                'email' => $data['email'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'code' => $data['zip'],
                'city' => $data['city'],
                'country' => $data['country'],
            ],
            'callbacks' => [
                'payerUrls' => [
                    'success' => $data['return_url'],
                    'error' => $data['return_error_url'],
                ],
                'notification' => ['url' => $data['result_url']],
            ],
        ];

        if ($data['group'] && $data['channel']) {
            throw OpenApiException::channelAndGroupCollision();
        }

        if ($data['group']) {
            $paymentData['pay']['groupId'] = $data['group'];
        }

        if ($data['channel']) {
            $paymentData['pay']['channelId'] = $data['channel'];
        }

        if ($data['tax_id']) {
            $paymentData['payer']['taxId'] = $data['tax_id'];
        }

        return $paymentData;
    }

    private function updateRedirectUrl(array $transactionData): array
    {
        $blik0Url = null;
        if (!isset($transactionData['transactionPaymentUrl']) && 'success' === $transactionData['result']) {
            $blik0Url = 'blik0url';
        }
        $transactionData['url'] = $transactionData['transactionPaymentUrl'] ?? $blik0Url;

        return $transactionData;
    }

    private function waitForBlikAccept(array $result): array
    {
        if ('success' == $result['result']) {
            $stop = false;
            $i = 0;
            do {
                $correct = false;
                $tpayStatus = $this->tpayApi->transactions()->getTransactionById($result['transactionId']);
                $errors = 0;

                foreach ($tpayStatus['payments']['attempts'] as $error) {
                    if ('' != $error['paymentErrorCode']) {
                        $errors++;
                    }
                }

                if ('correct' == $tpayStatus['status']) {
                    $correct = true;
                }

                if (60 == $i || $correct) {
                    $stop = true;
                }

                if ($errors > 0 && !$correct) {
                    $stop = true;
                    $result['payments']['errors'] = $tpayStatus['payments']['attempts'];
                }

                sleep(1);
                $i++;
            } while (!$stop);

            return $result;
        }
        $result['payments']['errors'] = [1];

        return $result;
    }

    private function getAuthTokenCacheKey(TpayConfigInterface $tpay, ?int $storeId = null)
    {
        return sprintf(
            self::AUTH_TOKEN_CACHE_KEY,
            md5(
                join('|', [$tpay->getOpenApiClientId($storeId), $tpay->getOpenApiPassword($storeId), !$tpay->useSandboxMode($storeId)])
            )
        );
    }
}
