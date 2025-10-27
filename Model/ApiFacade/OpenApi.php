<?php

namespace Tpay\Magento2\Model\ApiFacade;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\Dto\Channel;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\CacheProvider;
use Tpay\OpenApi\Api\TpayApi;
use Tpay\OpenApi\Api\TpayApiFactory;
use Tpay\OpenApi\Utilities\TpayException;

class OpenApi
{
    public const AUTH_TOKEN_CACHE_KEY = 'tpay_auth_token_%s';
    public const VM_GROUP = 171;
    public const VM_CHANNEL = 79;

    /** @var TpayApi */
    private $tpayApi;

    /** @var CacheProvider */
    private $cache;

    /** @var int */
    private $storeId;

    public function __construct(TpayConfigInterface $tpay, CacheProvider $cache, StoreManagerInterface $storeManager, TpayApiFactory $apiFactory, ?int $storeId = null)
    {
        $this->storeId = null === $storeId ? $storeManager->getStore()->getId() : $storeId;
        $this->cache = $cache;
        $this->tpayApi = $apiFactory->create([
            'clientId' => $tpay->getOpenApiClientId($this->storeId),
            'clientSecret' => $tpay->getOpenApiPassword($this->storeId),
            'productionMode' => !$tpay->useSandboxMode($this->storeId),
            'clientName' => $tpay->buildMagentoInfo(),
        ]);
        $token = $this->cache->get($this->getAuthTokenCacheKey($tpay, $this->storeId));

        if ($token) {
            $this->tpayApi->setCustomToken($token);
        }

        $this->tpayApi->authorization();

        if (!$token) {
            $this->cache->set($this->getAuthTokenCacheKey($tpay, $this->storeId), $this->tpayApi->getToken(), 7100);
        }
    }

    public function create(array $data): array
    {
        if (!empty($data['blikPaymentData'])) {
            return $this->createBlikZero($data);
        }

        $transactionData = $this->handleDataStructure($data);

        return $this->tpayApi->transactions()->createTransaction($transactionData);
    }

    public function createTransaction(array $data): array
    {
        if (!empty($data['blikPaymentData']) && empty($data['blikPaymentData']['aliases'])) {
            return $this->createBlikZeroTransaction($data);
        }

        $transactionData = $this->handleDataStructure($data);

        return $this->tpayApi->transactions()->createTransaction($transactionData);
    }

    public function createWithInstantRedirect(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);

        return $this->tpayApi->transactions()->createTransactionWithInstantRedirection($transactionData);
    }

    public function createBlikZeroTransaction(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        unset($transactionData['pay']);

        return $this->tpayApi->transactions()->createTransactionWithInstantRedirection($transactionData);
    }

    public function blik(string $transactionId, array $blikPaymentData): array
    {
        $additional_payment_data = [
            'channelId' => 64,
            'blikPaymentData' => [
                'type' => 0,
                'blikToken' => $blikPaymentData['blikToken'],
            ],
        ];

        if (isset($blikPaymentData['aliases'])) {
            $additional_payment_data['blikPaymentData']['aliases'] = $blikPaymentData['aliases'];
        }

        return $this->tpayApi->transactions()->createInstantPaymentByTransactionId($additional_payment_data, $transactionId);
    }

    public function blikAlias(string $transactionId, array $aliases): array
    {
        $additional_payment_data = [
            'channelId' => 64,
            'method' => 'pay_by_link',
            'blikPaymentData' => [
                'type' => 0,
                'aliases' => $aliases,
            ],
        ];

        return $this->tpayApi->transactions()->createInstantPaymentByTransactionId($additional_payment_data, $transactionId);
    }

    public function createBlikZero(array $data): array
    {
        $transaction = $this->createBlikZeroTransaction($data);

        if (!empty($data['blikPaymentData']['aliases']) && empty($data['blikPaymentData']['blikToken'])) {
            return $this->blikAlias($transaction['transactionId'], $data['blikPaymentData']['aliases']);
        }

        return $this->blik($transaction['transactionId'], $data['blikPaymentData']);
    }

    public function makeRefund(InfoInterface $payment, float $amount): array
    {
        $result = $this->tpayApi->transactions()->createRefundByTransactionId(
            ['amount' => $amount],
            $payment->getAdditionalInformation('transaction_id')
        );

        if ('success' != $result['result'] ?? '') {
            $messages = [];
            foreach ($result['errors'] ?? [] as $error) {
                if (isset($error['errorMessage'])) {
                    $messages[] = $error['errorMessage'];
                }
            }

            throw new Exception(__('Payment refunding error. - %1', implode('; ', $messages)));
        }

        return $result;
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

    public function getBankGroups(bool $onlineOnly = false)
    {
        $cacheKey = 'tpay_bank_groups_'.$this->storeId;

        $channels = $this->cache->get($cacheKey);

        if ($channels) {
            return $channels;
        }

        try {
            $groups = $this->tpayApi->transactions()->getBankGroups($onlineOnly);
        } catch (TpayException $e) {
            return [];
        }
        $this->cache->set(
            $cacheKey,
            $groups,
            TransactionApiFacade::CACHE_LIFETIME
        );

        return $groups;
    }

    public function checkAuthorized()
    {
        return !empty($this->tpayApi->getToken());
    }

    public function cancel(string $transactionId)
    {
        $this->tpayApi->transactions()->cancelTransaction($transactionId);
    }

    public function getStatus(string $paymentId): ?array
    {
        return $this->tpayApi->transactions()->getTransactionById($paymentId);
    }

    private function handleDataStructure(array $data): array
    {
        $paymentData = [
            'amount' => $data['amount'],
            'description' => $data['description'],
            'hiddenDescription' => $data['crc'],
            'lang' => strstr($data['language'], '_', true) ?: $data['language'],
            'payer' => [
                'email' => $data['email'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'code' => $data['zip'],
                'city' => $data['city'],
                'country' => $data['country'],
                'ip' => $data['ip'],
                'userAgent' => substr($data['userAgent'], 0, 255),
            ],
            'callbacks' => [
                'payerUrls' => [
                    'success' => $data['return_url'],
                    'error' => $data['return_error_url'],
                ],
                'notification' => ['url' => $data['result_url']],
            ],
        ];

        if (!empty($data['group']) && !empty($data['channel'])) {
            throw OpenApiException::channelAndGroupCollision();
        }

        if (!empty($data['group'])) {
            $paymentData['pay']['groupId'] = $data['group'];

            if (self::VM_GROUP === $data['group']) {
                unset($paymentData['payer']['phone']);
            }
        }

        if (!empty($data['channel'])) {
            $paymentData['pay']['channelId'] = $data['channel'];

            if (self::VM_CHANNEL === $data['channel']) {
                unset($paymentData['payer']['phone']);
            }
        }

        if (!empty($data['tax_id'])) {
            $paymentData['payer']['taxId'] = $data['tax_id'];
        }

        return $paymentData;
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
        return sprintf(self::AUTH_TOKEN_CACHE_KEY, $storeId);
    }
}
