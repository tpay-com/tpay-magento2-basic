<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\TpayPayment;
use Tpay\Magento2\Service\TpayService;

class Retry implements ActionInterface, HttpPostActionInterface
{
    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var TransactionApiFacade */
    private $transactionApi;

    /** @var Http */
    private $request;

    /** @var TpayPayment */
    private $tpay;

    /** @var TpayService */
    private $service;

    /** @var Session */
    private $checkoutSession;

    public function __construct(JsonFactory $resultJsonFactory, OrderRepositoryInterface $orderRepository, TransactionApiFacade $transactionApi, Http $request, TpayPayment $tpay, TpayService $service, Session $checkoutSession)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->transactionApi = $transactionApi;
        $this->request = $request;
        $this->tpay = $tpay;
        $this->service = $service;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(): ResultInterface
    {
        $orderId = $this->request->getParam('order_id');
        $transactionId = $this->request->getParam('transaction_id');
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        $response = $this->resultJsonFactory->create();

        $orderTransactionId = $payment->getAdditionalInformation('transaction_id');
        if ($transactionId !== $orderTransactionId) {
            throw new InvalidRequestException($response->setData(['error' => true]));
        }

        $status = $this->transactionApi->getStatus($transactionId);

        if (in_array($status['status'], ['paid', 'correct', 'success'])) {
            return $response->setData(['status' => 'success']);
        }

        $disableBlik = count($status['payments']['attempts']) >= 3;

        $blikCode = $this->request->getParam('blikCode');

        if (!empty($blikCode) && preg_match('/^\d{6}$/', $blikCode)) {
            $result = $this->transactionApi->blik($transactionId, $blikCode, null);

            $this->checkoutSession->setTpayPreviousAttempts(count($status['payments']['attempts']));

            if (!empty($result['errors'])) {
                return $response->setData(['status' => 'failed', 'errorMessage' => $result['errors'][0]['errorMessage'], 'disableBlik' => $disableBlik]);
            }

            return $response->setData(['status' => 'wait', 'disableBlik' => $disableBlik]);
        }

        $this->transactionApi->cancel($transactionId);

        $data = $this->tpay->getTpayFormData($order->getIncrementId());
        $data = $this->transactionApi->originApiFieldCorrect($data);
        $transaction = $this->transactionApi->createTransaction($data);

        $payment->setAdditionalInformation('transaction_id', $transaction['transactionId']);
        $this->service->saveOrderPayment($payment);
        $this->service->addCommentToHistory($order->getIncrementId(), __('Retrying payment with redirect to paywall and new transaction %1, link: %2', $transaction['title'], $transaction['transactionPaymentUrl']));

        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getIncrementId());

        return $response->setData(['redirect' => $transaction['transactionPaymentUrl']]);
    }
}
