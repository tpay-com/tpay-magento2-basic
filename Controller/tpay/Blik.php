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
use tpaycom\magento2basic\Service\TpayService;

/**
 * Class Blik
 *
 * @package tpaycom\magento2basic\Controller\tpay
 */
class Blik extends Action
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
     * @param TpayInterface      $tpayModel
     * @param TransactionFactory $transactionFactory
     * @param TpayService        $tpayService
     */
    public function __construct(
        Context $context,
        TpayInterface $tpayModel,
        TransactionFactory $transactionFactory,
        TpayService $tpayService,
        Session $checkoutSession
    ) {
        $this->tpay               = $tpayModel;
        $this->transactionFactory = $transactionFactory;
        $this->tpayService        = $tpayService;
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
            $paymentData = $this->tpayService->getPaymentData($orderId);

            $this->tpayService->setOrderStatePendingPayment($orderId, true);

            $pass = $this->tpay->getApiPassword();
            $key  = $this->tpay->getApiKey();

            $this->transaction = $this->transactionFactory->create(['apiPassword' => $pass, 'apiKey' => $key]);

            $additionalPaymentInformation = $paymentData['additional_information'];

            $result = $this->makeBlikPayment($orderId, $additionalPaymentInformation);
            $this->checkoutSession->unsQuoteId();

            if (!$result) {
                return $this->_redirect('magento2basic/tpay/error');
            }

            return $this->_redirect('magento2basic/tpay/success');
        }
    }

    /**
     * Create  BLIK Payment for transaction data
     *
     * @param int   $orderId
     * @param array $additionalPaymentInformation
     *
     * @return bool
     */
    protected function makeBlikPayment($orderId, array $additionalPaymentInformation)
    {
        $data     = $this->tpay->getTpayFormData($orderId);
        $blikCode = $additionalPaymentInformation['blik_code'];

        unset($additionalPaymentInformation['blik_code']);

        $data = array_merge($data, $additionalPaymentInformation);

        $blikTransactionId = $this->transaction->createBlikTransaction($data);

        if (!$blikTransactionId) {
            return false;
        }

        return $this->blikPay($blikTransactionId, $blikCode);
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
