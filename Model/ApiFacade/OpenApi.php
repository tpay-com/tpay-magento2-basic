<?php

namespace tpaycom\magento2basic\Model\ApiFacade;

use Magento\Payment\Model\InfoInterface;
use tpaySDK\Api\TpayApi;

class OpenApi extends TpayApi
{
    public function create(array $data): array
    {
        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->Transactions->createTransaction($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function createWithInstantRedirect(array $data)
    {
        $transactionData = $this->handleDataStructure($data);
        $transaction = $this->Transactions->createTransactionWithInstantRedirection($transactionData);

        return $this->updateRedirectUrl($transaction);
    }

    public function makeRefund(InfoInterface $payment, string $amount): array
    {
        return $this->Transactions->createRefundByTransactionId(['amount' => number_format($amount, 2)], $payment->getAdditionalInformation('transaction_id'));
    }

    public function channels(): array
    {
        return $this->transactions()->getChannels();
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
            "callbacks" => [
                "payerUrls" => [
                    "success" => $data['return_url'],
                    "error" => $data['return_error_url']
                ],
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
