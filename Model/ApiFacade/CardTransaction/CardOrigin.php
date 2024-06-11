<?php

namespace Tpay\Magento2\Model\ApiFacade\CardTransaction;

use Exception;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;
use Tpay\OriginApi\Notifications\CardNotificationHandler;

class CardOrigin extends CardNotificationHandler
{
    /** @var TpayInterface */
    private $tpay;

    /** @var TpayTokensService */
    private $tokensService;

    /** @var TpayService */
    private $tpayService;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    private $tpayPaymentConfig;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, TpayTokensService $tokensService, TpayService $tpayService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->tokensService = $tokensService;
        $this->tpayService = $tpayService;
        $this->cardApiKey = $tpayConfig->getCardApiKey();
        $this->cardApiPass = $tpayConfig->getCardApiPassword();
        $this->cardVerificationCode = $tpayConfig->getVerificationCode();
        $this->cardKeyRSA = $tpayConfig->getRSAKey();
        $this->cardHashAlg = $tpayConfig->getHashType();
        parent::__construct();
    }

    public function makeFullCardTransactionProcess(string $orderId, ?array $customerToken = null): string
    {
        $payment = $this->tpayService->getPayment($orderId);
        $paymentData = $payment->getData();

        $this->tpayService->setOrderStatePendingPayment($orderId, false);
        $additionalPaymentInformation = $paymentData['additional_information'];

        $this->tpayPaymentConfig = $this->tpay->getTpayFormData($orderId);

        $this
            ->setEnablePowUrl(true)
            ->setReturnUrls($this->tpayPaymentConfig['return_url'], $this->tpayPaymentConfig['return_error_url'])
            ->setAmount($this->tpayPaymentConfig['amount'])
            ->setCurrency($this->tpayPaymentConfig['currency'])
            ->setLanguage(strtolower($this->tpayPaymentConfig['language']))
            ->setOrderID($this->tpayPaymentConfig['crc'])
            ->setModuleName($this->tpayPaymentConfig['module']);

        if (isset($additionalPaymentInformation['card_id']) && false !== $additionalPaymentInformation['card_id'] && $this->tpayConfig->getCardSaveEnabled()) {
            $cardId = (int) $additionalPaymentInformation['card_id'];

            return $this->processSavedCardPayment($orderId, $cardId, $customerToken);
        }

        return $this->processNewCardPayment($orderId, $additionalPaymentInformation);
    }

    private function processSavedCardPayment(string $orderId, int $cardId, ?array $customerToken = null): string
    {
        $customerToken = $customerToken ? $customerToken : $this->tokensService->getTokenById($cardId, $this->tpay->getCustomerId($orderId), false);

        if ($customerToken) {
            $token = $customerToken['cli_auth'];
            try {
                $paymentResult = $this->presale($this->tpayPaymentConfig['description'], $token);

                if (isset($paymentResult['sale_auth'])) {
                    $paymentResult = $this->sale($paymentResult['sale_auth'], $token);
                }

                if (1 === (int) $paymentResult['result'] && isset($paymentResult['status']) && 'correct' === $paymentResult['status']) {
                    $this->tpayService->setCardOrderStatus($orderId, $paymentResult, $this->tpayConfig);
                    $this->tpayService->addCommentToHistory($orderId, 'Successful payment by saved card');

                    return 'magento2basic/tpay/success';
                }

                if (isset($paymentResult['status']) && 'declined' === $paymentResult['status']) {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'Failed to pay by saved card, Elavon rejection code: '.$paymentResult['reason']
                    );
                } else {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'Failed to pay by saved card, error: '.$paymentResult['err_desc']
                    );
                }
            } catch (Exception $e) {
                return $this->trySaleAgain($orderId);
            }
        } else {
            $this->tpayService->addCommentToHistory($orderId, 'Attempt of payment by not owned card has been blocked!');
        }

        return $this->trySaleAgain($orderId);
    }

    private function trySaleAgain(string $orderId): string
    {
        $this->setCardData(null);
        $result = $this->registerSale($this->tpayPaymentConfig['name'], $this->tpayPaymentConfig['email'], $this->tpayPaymentConfig['description']);

        if (isset($result['sale_auth'])) {
            $url = 'https://secure.tpay.com/cards?sale_auth='.$result['sale_auth'];
            $this->tpayService->addCommentToHistory($orderId, 'Customer has been redirected to tpay.com transaction panel. Transaction link '.$url);
            $this->addToPaymentData($orderId, 'transaction_url', $url);

            return $url;
        }

        return 'magento2basic/tpay/success';
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
        if (true === $saveCard) {
            $this->setOneTimer(false);
        }
        try {
            $result = $this->createNewCardPayment($additionalPaymentInformation);
        } catch (Exception $e) {
            return $this->trySaleAgain($orderId);
        }
        if (isset($result['3ds_url'])) {
            $url3ds = $result['3ds_url'];
            $this->tpayService->addCommentToHistory($orderId, '3DS Transaction link '.$url3ds);
            $this->addToPaymentData($orderId, 'transaction_url', $url3ds);

            return $url3ds;
        }
        if (isset($result['status']) && 'correct' === $result['status']) {
            $this->validateNon3dsSign($result);
            $this->tpayService->setCardOrderStatus($orderId, $result, $this->tpayConfig);
        }

        if (isset($result['cli_auth'], $result['card']) && !$this->tpay->isCustomerGuest($orderId)) {
            $this->tokensService
                ->setCustomerToken(
                    $this->tpay->getCustomerId($orderId),
                    $result['cli_auth'],
                    $result['card'],
                    $additionalPaymentInformation['card_vendor']
                );
        }

        return 1 === (int) $result['result'] && isset($result['status']) && 'correct' === $result['status'] ? 'magento2basic/tpay/success' : $this->trySaleAgain($orderId);
    }

    private function createNewCardPayment(array $additionalPaymentInformation): array
    {
        $cardData = str_replace(' ', '+', $additionalPaymentInformation['card_data']);

        return $this->registerSale(
            $this->tpayPaymentConfig['name'],
            $this->tpayPaymentConfig['email'],
            $this->tpayPaymentConfig['description'],
            $cardData
        );
    }

    private function validateNon3dsSign(array $tpayResponse)
    {
        $testMode = isset($tpayResponse['test_mode']) ? '1' : '';
        $cliAuth = $tpayResponse['cli_auth'] ?? '';
        $localHash = hash(
            $this->tpayConfig->getHashType(),
            $testMode
            .$tpayResponse['sale_auth']
            .$cliAuth
            .$tpayResponse['card']
            .$this->tpayPaymentConfig['currency']
            .$this->tpayPaymentConfig['amount']
            .$tpayResponse['date']
            .$tpayResponse['status']
            .$this->tpayConfig->getVerificationCode()
        );
        if ($tpayResponse['sign'] !== $localHash) {
            throw new Exception('Card payment - invalid checksum');
        }
    }
}
