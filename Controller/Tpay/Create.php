<?php

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResponseInterface;
use Tpay\OriginApi\Utilities\Util;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Api\TpayInterface;
use TpayCom\Magento2Basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use TpayCom\Magento2Basic\Model\ApiFacade\Transaction\TransactionOriginApi;
use TpayCom\Magento2Basic\Model\TpayPayment;
use TpayCom\Magento2Basic\Service\TpayService;

class Create extends Action
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

    /** @var CacheInterface */
    private $cache;

    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        Session $checkoutSession,
        CacheInterface $cache
    ) {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->cache = $cache;
        Util::$loggingEnabled = false;

        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $paymentData = $payment->getData();
            $this->transaction = new TransactionApiFacade($this->tpayConfig, $this->cache);
            $additionalPaymentInformation = $paymentData['additional_information'];

            if (!$additionalPaymentInformation[TpayPayment::TERMS_ACCEPT]) {
                return $this->_redirect('magento2basic/tpay/error');
            }

            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->_redirect('magento2basic/tpay/error');
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

            if (6 === strlen($additionalPaymentInformation['blik_code'] ?? '') && $this->tpay->checkBlikLevel0Settings()) {
                if (true === $this->transaction->isOpenApiUse()) {
                    if (isset($transaction['payments']['errors']) && count($transaction['payments']['errors']) > 0) {
                        return $this->_redirect('magento2basic/tpay/error');
                    }

                    return $this->_redirect('magento2basic/tpay/success');
                }
                $result = $this->blikPay($transaction['title'], $additionalPaymentInformation['blik_code']);
                $this->checkoutSession->unsQuoteId();

                if (!$result) {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'User has typed wrong blik code and has been redirected to transaction panel in order to finish payment'
                    );

                    return $this->_redirect('magento2basic/tpay/error');
                }

                return $this->_redirect('magento2basic/tpay/success');
            }

            return $this->_redirect($transactionUrl);
        }

        return $this->_redirect('magento2basic/tpay/error');
    }

    /**
     * Send BLIK code for transaction id
     *
     * @param string $blikTransactionId
     * @param string $blikCode
     */
    protected function blikPay($blikTransactionId, $blikCode): bool
    {
        $apiResult = $this->transaction->blik($blikTransactionId, $blikCode);

        return isset($apiResult['result']) && 1 === $apiResult['result'];
    }

    private function prepareTransaction($orderId, array $additionalPaymentInformation)
    {
        $data = $this->tpay->getTpayFormData($orderId);

        if (6 === strlen($additionalPaymentInformation['blik_code'] ?? '')) {
            $data['group'] = TransactionOriginApi::BLIK_CHANNEL;
            $data['channel'] = null;
            $this->handleBlikData($data, $additionalPaymentInformation['blik_code']);
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

    private function handleBlikData(array &$data, string $blikCode)
    {
        if ($this->transaction->isOpenApiUse() && $this->tpay->checkBlikLevel0Settings()) {
            $data['blikPaymentData'] = [
                'blikToken' => $blikCode,
            ];
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
