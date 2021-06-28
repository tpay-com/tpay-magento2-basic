<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\TransactionFactory;
use tpaycom\magento2basic\Model\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction as MagentoTransaction;
use tpaycom\magento2basic\Service\TpayService;

/**
 * Class Blik
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
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
     * @var Transaction
     */
    private $transaction;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * {@inheritdoc}
     *
     * @param TpayInterface $tpayModel
     * @param TransactionFactory $transactionFactory
     * @param TpayService $tpayService
     */
    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TransactionFactory $transactionFactory,
        TpayService $tpayService,
        Session $checkoutSession

    ) {
        $this->tpay = $tpayModel;
        $this->transactionFactory = $transactionFactory;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $payment = $this->tpayService->getPayment($orderId);
            $paymentData = $payment->getData();

            $pass = $this->tpay->getApiPassword();
            $key = $this->tpay->getApiKey();

            $this->transaction = $this->transactionFactory->create(['apiPassword' => $pass, 'apiKey' => $key]);

            $additionalPaymentInformation = $paymentData['additional_information'];

            $transaction = $this->prepareTransaction($orderId, $additionalPaymentInformation);

            if (!isset($transaction['title'], $transaction['url'])) {
                return $this->_redirect('magento2basic/tpay/error');
            }
            $this->tpayService->addCommentToHistory($orderId, 'Transaction title ' . $transaction['title']);
            $transactionUrl = $transaction['url'];
            $this->tpayService->addCommentToHistory($orderId, 'Transaction link ' . $transactionUrl);

            $paymentData['additional_information']['transaction_url'] = $transactionUrl;
            $payment->setData($paymentData)->save();

            if (!empty($additionalPaymentInformation['blik_code'])
                && $this->tpay->checkBlikLevel0Settings()
                && $additionalPaymentInformation['kanal'] == Transaction::BLIK_CHANNEL
            ) {
                $code = $additionalPaymentInformation['blik_code'];
                $result = $this->blikPay($transaction['title'], $code);
                $this->checkoutSession->unsQuoteId();
                if (!$result) {
                    $this->tpayService->addCommentToHistory($orderId,
                        'User has typed wrong blik code and has been redirected to transaction panel in order to finish payment');
                    return $this->_redirect($transactionUrl);
                } else {
                    return $this->_redirect('magento2basic/tpay/success');
                }
            } else {
                return $this->_redirect($transactionUrl);
            }
        }
    }

    private function prepareTransaction($orderId, array $additionalPaymentInformation)
    {
        $data = $this->tpay->getTpayFormData($orderId);
        $channel = $additionalPaymentInformation['kanal'];

        return $this->transaction->createTransaction($data, $channel);
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
        return $this->transaction->sendBlikCode($blikTransactionId, $blikCode);
    }
}
