<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;

class Status implements ActionInterface, HttpGetActionInterface
{
    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var TransactionApiFacade */
    private $transactionApi;

    /** @var RequestInterface */
    private $request;

    public function __construct(JsonFactory $resultJsonFactory, OrderRepositoryInterface $orderRepository, TransactionApiFacade $transactionApi, RequestInterface $request)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->transactionApi = $transactionApi;
        $this->request = $request;
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

        $previousAttempts = (int)($payment->getAdditionalInformation('payment_attempts_count') ?? 1);
        if (count($status['payments']['attempts']) >= $previousAttempts) {
            return $response->setData(['status' => 'failed', 'errorMessage' => __('Payment failed. Try again.')]);
        }

        return $response->setData(['status' => 'wait']);
    }
}
