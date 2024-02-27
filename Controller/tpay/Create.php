<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;
use tpaycom\magento2basic\Model\Tpay;
use tpaycom\magento2basic\Service\TpayService;

class Create implements ActionInterface
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var TpayInterface */
    private $tpay;

    /** @var TransactionApiFacade */
    private $transaction;

    /** @var RedirectFactory */
    private $redirectFactory;

    public function __construct(
        TpayInterface $tpayModel,
        TpayService $tpayService,
        Session $checkoutSession,
        TransactionApiFacade $transactionApiFacade,
        RedirectFactory $redirectFactory
    ) {
        $this->tpay = $tpayModel;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->transaction = $transactionApiFacade;
        $this->redirectFactory = $redirectFactory;
        Util::$loggingEnabled = false;
    }

    public function execute(): ResultInterface
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $paymentData = $payment->getData();
            $additionalPaymentInformation = $paymentData['additional_information'];

            if (!$additionalPaymentInformation[Tpay::TERMS_ACCEPT]) {
                return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
            }

            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
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
                    if (isset($transaction['payments']['errors']) && count($transaction['payments']['errors']) > 0) {
                        return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
                    }

                    return $this->redirectFactory->create()->setPath('magento2basic/tpay/success');
                }
                $result = $this->blikPay($transaction['title'], $additionalPaymentInformation['blik_code']);
                $this->checkoutSession->unsQuoteId();

                if (!$result) {
                    $this->tpayService->addCommentToHistory(
                        $orderId,
                        'User has typed wrong blik code and has been redirected to transaction panel in order to finish payment'
                    );

                    return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
                }

                return $this->redirectFactory->create()->setPath('magento2basic/tpay/success');
            }

            return $this->redirectFactory->create()->setPath($transactionUrl);
        }

        return $this->redirectFactory->create()->setPath('magento2basic/tpay/error');
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

            if ($this->tpay->redirectToChannel()) {
                $data['direct'] = 1;
            }
        }

        $data = $this->transaction->originApiFieldCorrect($data);
        $data = $this->transaction->translateGroupToChannel($data, $this->tpay->redirectToChannel());

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
