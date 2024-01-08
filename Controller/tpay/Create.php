<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResponseInterface;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;
use tpaycom\magento2basic\Service\TpayService;

class Create extends Action
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var TpayInterface */
    private $tpay;

    /** @var TransactionApiFacade */
    private $transaction;

    /** @var CacheInterface */
    private $cache;

    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TpayService $tpayService,
        Session $checkoutSession,
        CacheInterface $cache
    ) {
        $this->tpay = $tpayModel;
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
            $this->transaction = new TransactionApiFacade($this->tpay, $this->cache);
            $additionalPaymentInformation = $paymentData['additional_information'];

            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->_redirect('magento2basic/tpay/error');
            }

            $this->handleOpenApiTrId($paymentData, $transaction);

            $this->tpayService->addCommentToHistory($orderId, 'Transaction title '.$transaction['title']);
            $transactionUrl = $transaction['url'];

            if (true === $this->tpay->redirectToChannel()) {
                $transactionUrl = str_replace('gtitle', 'title', $transactionUrl);
            }

            $this->tpayService->addCommentToHistory($orderId, 'Transaction link '.$transactionUrl);
            $paymentData['additional_information']['transaction_url'] = $transactionUrl;
            $payment->setData($paymentData)->save();

            if (6 === strlen($additionalPaymentInformation['blik_code'] ?? '') && $this->tpay->checkBlikLevel0Settings()) {
                if (true === $this->transaction->isOpenApiUse()) {
                    return $this->_redirect('magento2basic/tpay/success');
                }
                $result = $this->blikPay($transaction['title'], $additionalPaymentInformation['blik_code']);
                $this->checkoutSession->unsQuoteId();

                if (!$result) {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'User has typed wrong blik code and has been redirected to transaction panel in order to finish payment'
                    );

                    return $this->_redirect($transactionUrl);
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
            $this->handleBlikData($data, $additionalPaymentInformation['blik_code']);
        } else {
            $data['group'] = (int) ($additionalPaymentInformation['group'] ?? null);
            $data['channel'] = (int) ($additionalPaymentInformation['channel'] ?? null);

            if ($this->tpay->redirectToChannel()) {
                $data['direct'] = 1;
            }
        }

        if ($data['channel']) {
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
    }

    private function handleOpenApiTrId(array &$paymentData, array $transaction)
    {
        if (isset($transaction['transactionId'])) {
            $paymentData['additional_information']['transaction_id'] = $transaction['transactionId'];
        }
    }
}
