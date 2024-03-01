<?php

namespace TpayCom\Magento2Basic\Model\ApiFacade\CardTransaction;

use Exception;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Service\TpayService;
use TpayCom\Magento2Basic\Service\TpayTokensService;
use tpaySDK\Api\TpayApi;

class CardOpen
{
    /** @var TpayInterface */
    private $tpay;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var TpayService */
    private $tpayService;

    /** @var TpayApi */
    private $tpayApi;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    private $tpayPaymentConfig;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->tokensService = $tokensService;
        $this->tpayService = $tpayService;
        $this->tpayApi = new TpayApi($tpayConfig->getOpenApiClientId(), $tpayConfig->getOpenApiPassword(), !$tpayConfig->useSandboxMode());
        $versions = $this->getPackagesVersions();
        $this->tpayApi->authorization()->setClientName(implode(
            '|',
            [
                'magento2:'.$this->getMagentoVersion(),
                'tpay-com/tpay-openapi-php:'.$versions[0],
                'tpay-com/tpay-php:'.$versions[1],
                'PHP:'.phpversion(),
            ]
        ));
    }

    public function makeCardTransaction(string $orderId): string
    {
        $payment = $this->tpayService->getPayment($orderId);
        $paymentData = $payment->getData();

        $this->tpayService->setOrderStatePendingPayment($orderId, false);
        $additionalPaymentInformation = $paymentData['additional_information'];

        $this->tpayPaymentConfig = $this->tpay->getTpayFormData($orderId);

        if (isset($additionalPaymentInformation['card_id']) && false !== $additionalPaymentInformation['card_id'] && $this->tpayConfig->getCardSaveEnabled()) {
            $cardId = (int) $additionalPaymentInformation['card_id'];

            return $this->processSavedCardPayment($orderId, $cardId);
        }

        return $this->processNewCardPayment($orderId, $additionalPaymentInformation);
    }

    private function processSavedCardPayment(string $orderId, int $cardId): string
    {
        $customerToken = $this->tokensService->getTokenById($cardId, $this->tpay->getCustomerId($orderId));

        if ($customerToken) {
            try {
                $transaction = $this->tpayApi->transactions()->createTransaction($this->handleDataStructure());

                $request = [
                    'groupId' => 103,
                    'cardPaymentData' => [
                        'token' => $customerToken['cli_auth'],
                    ],
                    'method' => 'sale',
                ];

                $result = $this->tpayApi->transactions()->createPaymentByTransactionId($request, $transaction['transactionId']);

                if ('success' === $result['result'] && isset($result['payments']['status']) && 'correct' === $result['payments']['status']) {
                    $this->tpayService->setCardOrderStatus($orderId, $this->handleValidParams($result), $this->tpayConfig);
                    $this->tpayService->addCommentToHistory($orderId, 'Successful payment by saved card');

                    return 'magento2basic/tpay/success';
                }

                $paymentResult = $result['payments'] ?? [];

                if (isset($paymentResult['status']) && 'declined' === $paymentResult['status']) {
                    $this->tpayService->addCommentToHistory($orderId, 'Failed to pay by saved card, Elavon rejection code: '.$paymentResult['reason']);
                } else {
                    $this->tpayService->addCommentToHistory($orderId, 'Failed to pay by saved card, error: '.$paymentResult['err_desc']);
                }
            } catch (Exception $e) {
                return 'magento2basic/tpay/error';
            }
        } else {
            $this->tpayService->addCommentToHistory($orderId, 'Attempt of payment by not owned card has been blocked!');
        }

        return 'magento2basic/tpay/error';
    }

    private function addToPaymentData(string $orderId, string $key, $value)
    {
        $payment = $this->tpayService->getPayment($orderId);
        $paymentData = $payment->getData();
        $paymentData['additional_information'][$key] = $value;
        $payment->setData($paymentData);
        $this->tpayService->saveOrderPayment($payment);
    }

    private function processNewCardPayment(string $orderId, array $additionalPaymentInformation): string
    {
        $saveCard = isset($additionalPaymentInformation['card_save']) && $this->tpayConfig->getCardSaveEnabled() ? (bool) $additionalPaymentInformation['card_save'] : false;
        try {
            $transaction = $this->tpayApi->transactions()->createTransaction($this->handleDataStructure());
            $request = [
                'groupId' => 103,
                'cardPaymentData' => [
                    'card' => $additionalPaymentInformation['card_data'],
                    'save' => $saveCard,
                ],
                'method' => 'pay_by_link',
            ];
            $result = $this->tpayApi->transactions()->createPaymentByTransactionId($request, $transaction['transactionId']);
            $this->tpayService->setCardOrderStatus($orderId, $this->handleValidParams($result), $this->tpayConfig);
        } catch (Exception $e) {
            return 'magento2basic/tpay/error';
        }

        if (isset($result['transactionPaymentUrl']) && 'pending' === $result['payments']['status']) {
            $url3ds = $result['transactionPaymentUrl'];
            $this->tpayService->addCommentToHistory($orderId, '3DS Transaction link '.$url3ds);
            $this->addToPaymentData($orderId, 'transaction_url', $url3ds);
            $this->saveCard($orderId, $saveCard, $additionalPaymentInformation);

            return $url3ds;
        }

        return 'magento2basic/tpay/error';
    }

    private function saveCard(string $orderId, bool $saveCard, array $additionalPaymentInformation)
    {
        if ($saveCard && !$this->tpay->isCustomerGuest($orderId)) {
            $this->tokensService->setCustomerToken(
                $this->tpay->getCustomerId($orderId),
                null,
                $additionalPaymentInformation[TpayInterface::SHORT_CODE],
                $additionalPaymentInformation[TpayInterface::CARD_VENDOR],
                $this->tpayPaymentConfig['crc']
            );
        }
    }

    private function handleDataStructure(): array
    {
        return [
            'amount' => (float) $this->tpayPaymentConfig['amount'],
            'description' => $this->tpayPaymentConfig['description'],
            'hiddenDescription' => $this->tpayPaymentConfig['crc'],
            'payer' => [
                'email' => $this->tpayPaymentConfig['email'],
                'name' => $this->tpayPaymentConfig['name'],
                'phone' => $this->tpayPaymentConfig['phone'],
                'address' => $this->tpayPaymentConfig['address'],
                'code' => $this->tpayPaymentConfig['zip'],
                'city' => $this->tpayPaymentConfig['city'],
                'country' => $this->tpayPaymentConfig['country'],
            ],
            'lang' => 'pl',
            'pay' => [
                'groupId' => 103,
            ],
            'callbacks' => [
                'notification' => [
                    'url' => $this->tpayPaymentConfig['result_url'],
                ],
                'payerUrls' => [
                    'success' => $this->tpayPaymentConfig['return_url'],
                    'error' => $this->tpayPaymentConfig['return_error_url'],
                ],
            ],
        ];
    }

    private function handleValidParams(array $response): array
    {
        $response['status'] = $response['payments']['status'];
        $response['sale_auth'] = $response['transactionId'];

        return $response;
    }

    private function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('\Magento\Framework\App\ProductMetadataInterface');

        return $productMetadata->getVersion();
    }

    private function getPackagesVersions()
    {
        $dir = __DIR__.'/../../composer.json';
        if (file_exists($dir)) {
            $composerJson = json_decode(
                file_get_contents(__DIR__.'/../../composer.json'),
                true
            )['require'] ?? [];

            return [$composerJson['tpay-com/tpay-openapi-php'], $composerJson['tpay-com/tpay-php']];
        }

        return ['n/a', 'n/a'];
    }
}
