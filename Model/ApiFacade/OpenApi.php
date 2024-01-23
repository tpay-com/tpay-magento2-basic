<?php

namespace tpaycom\magento2basic\Model\ApiFacade;

use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\Dto\Channel;
use tpaySDK\Api\TpayApi;

class OpenApi extends TpayApi
{
    public function __construct($clientId, $clientSecret, $productionMode = false, $scope = 'read')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->productionMode = $productionMode;
        $this->scope = $scope;
        $versions = $this->getPackagesVersions();
        $this->setClientName(implode('|', [
                'magento2:' . $this->getMagentoVersion(),
                'tpay-com/tpay-openapi-php:' . $versions[0],
                'tpay-com/tpay-php:' . $versions[1],
                'PHP:' . phpversion()
            ]
        );
    }

    public function create(array $data): array
    {
        if (!empty($data['blikPaymentData'])) {
            return $this->createBlikZero($data);
        }

        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->transactions()->createTransaction($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createWithInstantRedirect(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->transactions()->createTransactionWithInstantRedirection($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createBlikZero(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        unset($transactionData['pay']);

        $transaction = $this->transactions()->createTransactionWithInstantRedirection($transactionData);

        $additional_payment_data = [
            'channelId' => 64,
            'method' => 'pay_by_link',
            'blikPaymentData' => [
                'type' => 0,
                'blikToken' => $data['blikPaymentData']['blikToken'],
            ],
        ];

        $result = $this->transactions()->createInstantPaymentByTransactionId($additional_payment_data, $transaction['transactionId']);

        return $this->updateRedirectUrl($this->waitForBlikAccept($result));
    }

    public function makeRefund(InfoInterface $payment, float $amount): array
    {
        return $this->transactions()->createRefundByTransactionId(
            ['amount' => $amount],
            $payment->getAdditionalInformation('transaction_id')
        );
    }

    public function channels(): array
    {
        $result = $this->transactions()->getChannels();
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

        if ($data['group']) {
            $paymentData['pay']['groupId'] = $data['group'];
        }

        if ($data['channel']) {
            $paymentData['pay']['channelId'] = $data['channel'];
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
                $tpayStatus = $this->transactions()->getTransactionById($result['transactionId']);
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

    private function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('\Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }

    private function getPackagesVersions()
    {
        $dir = __DIR__ . '/../../composer.json';
        if (file_exists($dir)) {
            $composerJson = json_decode(
                file_get_contents(__DIR__ . '/../../../composer.json'), true
            )['require'] ?? [];

            return [$composerJson['tpay-com/tpay-openapi-php'], $composerJson['tpay-com/tpay-php']];
        }
        return ['n/a', 'n/a'];
    }
}
