<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\TransactionModel;
use tpaycom\magento2basic\Model\TransactionModelFactory;
use tpaycom\magento2basic\Service\TpayService;
use tpayLibs\src\_class_tpay\Utilities\Util;

class Create extends Action
{
    /**
     * @var TpayService
     */
    protected $tpayService;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var TpayInterface
     */
    private $tpay;

    /**
     * @var TransactionModel
     */
    private $transaction;

    /**
     * @var TransactionModelFactory
     */
    private $transactionFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TransactionModelFactory $transactionModelFactory,
        TpayService $tpayService,
        Session $checkoutSession
    ) {
        $this->tpay = $tpayModel;
        $this->transactionFactory = $transactionModelFactory;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        Util::$loggingEnabled = false;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var int $orderId */
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            /** @var array<string> $paymentData */
            $paymentData = $payment->getData();

            $this->transaction = $this->transactionFactory->create(
                [
                    'apiPassword' => $this->tpay->getApiPassword(),
                    'apiKey' => $this->tpay->getApiKey(),
                    'merchantId' => $this->tpay->getMerchantId(),
                    'merchantSecret' => $this->tpay->getSecurityCode(),
                ]
            );
            $additionalPaymentInformation = $paymentData['additional_information'];

            /** @var array<string> $transaction */
            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->_redirect('magento2basic/tpay/error');
            }
            $this->tpayService->addCommentToHistory($orderId, 'Transaction title '.$transaction['title']);
            $transactionUrl = $transaction['url'];
            if (true === $this->tpay->redirectToChannel()) {
                $transactionUrl = str_replace('gtitle', 'title', $transactionUrl);
            }
            $this->tpayService->addCommentToHistory($orderId, 'Transaction link '.$transactionUrl);
            $paymentData['additional_information']['transaction_url'] = $transactionUrl;
            $payment->setData($paymentData)->save();

            if (6 === strlen($additionalPaymentInformation['blik_code'])
                && $this->tpay->checkBlikLevel0Settings()
            ) {
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
    }

    /**
     * Send BLIK code for transaction id
     *
     * @param string $blikTransactionId
     * @param string $blikCode
     *
     * @return bool
     */
    protected function blikPay($blikTransactionId, $blikCode)
    {
        /** @var array<string, mixed> $apiResult */
        $apiResult = $this->transaction->blik($blikTransactionId, $blikCode);

        return isset($apiResult['result']) && 1 === $apiResult['result'];
    }

    /**
     * @param array{blik_code: string, group: int|string} $additionalPaymentInformation
     */
    private function prepareTransaction($orderId, array $additionalPaymentInformation)
    {
        $data = $this->tpay->getTpayFormData($orderId);
        if (6 === strlen($additionalPaymentInformation['blik_code'])) {
            $data['group'] = TransactionModel::BLIK_CHANNEL;
        } else {
            $data['group'] = (int)$additionalPaymentInformation['group'];
        }

        return $this->transaction->create($data);
    }
}
