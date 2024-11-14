<?php

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;
use Tpay\Magento2\Service\RedirectHandler;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Validator\AdditionalPaymentInfoValidator;
use Tpay\OriginApi\Utilities\Util;

class Create implements ActionInterface
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var TransactionApiFacade */
    private $transaction;

    /** @var RedirectHandler */
    private $redirectFactory;

    /** @var AdditionalPaymentInfoValidator */
    private $additionalPaymentInfoValidator;

    public function __construct(
        TpayInterface $tpayModel,
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        Session $checkoutSession,
        TransactionApiFacade $transactionApiFacade,
        RedirectHandler $redirectFactory,
        AdditionalPaymentInfoValidator $additionalPaymentInfoValidator
    ) {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->transaction = $transactionApiFacade;
        $this->redirectFactory = $redirectFactory;
        $this->additionalPaymentInfoValidator = $additionalPaymentInfoValidator;
        Util::$loggingEnabled = false;
    }

    public function execute(): ResultInterface
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $paymentData = $payment->getData();
            $additionalPaymentInformation = $paymentData['additional_information'];

            if (!$additionalPaymentInformation[TpayInterface::TERMS_ACCEPT]) {
                return $this->redirectFactory->redirectError();
            }

            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->redirectFactory->redirectError();
            }

            $this->handleOpenApiTrId($paymentData, $transaction);

            $this->tpayService->addCommentToHistory($orderId, 'Transaction title '.$transaction['title']);
            $transactionUrl = $transaction['url'];

            if (true === $this->tpayConfig->redirectToChannel()) {
                $transactionUrl = str_replace('gtitle', 'title', $transactionUrl);
            }

            $this->tpayService->addCommentToHistory($orderId, 'Transaction link '.$transactionUrl);
            $paymentData['additional_information']['transaction_url'] = $transactionUrl;
            $payment->setData($paymentData);
            $this->tpayService->saveOrderPayment($payment);

            if ($this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInformation) && $this->tpay->checkBlikLevel0Settings()) {
                if (true === $this->transaction->isOpenApiUse()) {
                    if (isset($transaction['payments']['errors']) && count($transaction['payments']['errors']) > 0) {
                        return $this->redirectFactory->redirectError();
                    }

                    return $this->redirectFactory->redirectSuccess();
                }

                $result = $this->blikPay($transaction['title'], $additionalPaymentInformation['blik_code'] ?? '', $additionalPaymentInformation['blik_alias'] ?? '');
                $this->checkoutSession->unsQuoteId();

                if (!$result) {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'User has typed wrong blik code and has been redirected to transaction panel in order to finish payment'
                    );

                    return $this->redirectFactory->redirectError();
                }

                return $this->redirectFactory->redirectSuccess();
            }

            return $this->redirectFactory->redirectTransaction($transactionUrl);
        }

        return $this->redirectFactory->redirectError();
    }

    /**
     * Send BLIK code for transaction id
     *
     * @param string $blikTransactionId
     * @param string $blikCode
     */
    protected function blikPay($blikTransactionId, $blikCode, $blikAlias): bool
    {
        if ($blikAlias) {
            $apiResult = $this->transaction->blikAlias($blikTransactionId, $blikAlias);
        } else {
            $apiResult = $this->transaction->blik($blikTransactionId, $blikCode);
        }

        return isset($apiResult['result']) && 1 === $apiResult['result'];
    }

    private function prepareTransaction($orderId, array $additionalPaymentInformation)
    {
        $data = $this->tpay->getTpayFormData($orderId);

        if ($this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInformation)) {
            $data['group'] = TransactionOriginApi::BLIK_CHANNEL;
            $data['channel'] = null;
            $this->handleBlikData($data, $additionalPaymentInformation['blik_code'] ?? '', $additionalPaymentInformation['blik_alias'] ?? '');
        } else {
            $data['group'] = (int) ($additionalPaymentInformation['group'] ?? null);
            $data['channel'] = (int) ($additionalPaymentInformation['channel'] ?? null);

            if ($this->tpayConfig->redirectToChannel()) {
                $data['direct'] = 1;
            }
        }

        $data = $this->transaction->originApiFieldCorrect($data);
        $data = $this->transaction->translateGroupToChannel($data, $this->tpayConfig->redirectToChannel());

        if (isset($data['channel']) && $data['channel']) {
            return $this->transaction->createWithInstantRedirection($data);
        }

        return $this->transaction->create($data);
    }

    private function handleBlikData(array &$data, string $blikCode, string $blikAlias)
    {
        if ($this->transaction->isOpenApiUse() && $this->tpay->checkBlikLevel0Settings()) {
            if ($blikCode) {
                $data['blikPaymentData'] = [
                    'blikToken' => $blikCode,
                ];
            }

            if ($blikAlias) {
                $data['blikPaymentData']['aliases'] = [
                    'type' => 'UID',
                    'value' => $blikAlias,
                ];
            }
        }
        if (!$this->transaction->isOpenApiUse()) {
            unset($data['channel']);
            unset($data['currency']);
            unset($data['language']);
        }
    }

    private function handleOpenApiTrId(array &$paymentData, array $transaction)
    {
        if (isset($transaction['transactionId'])) {
            $paymentData['additional_information']['transaction_id'] = $transaction['transactionId'];
        }
    }
}
