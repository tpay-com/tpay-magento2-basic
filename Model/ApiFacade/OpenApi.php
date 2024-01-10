<?php

namespace tpaycom\magento2basic\Model\ApiFacade;

use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\Dto\Channel;
use tpaySDK\Api\TpayApi;

class OpenApi extends TpayApi
{
    public function create(array $data): array
    {
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

        return array_map(function (array $channel) {
            return new Channel(
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
        }, $result['channels'] ?? []);
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

        if (!empty($data['blikPaymentData'])) {
            $paymentData['pay']['blikPaymentData'] = [
                'blikToken' => $data['blikPaymentData']['blikToken'],
            ];
        }

        if ($data['group']) {
            $paymentData['pay'] = ['groupId' => $data['group']];
        }

        if ($data['channel']) {
            $paymentData['pay'] = ['channelId' => $data['channel']];
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
}
