<?php

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;
use Tpay\Magento2\Provider\ConfigurationProvider;
use Tpay\Magento2\Service\RedirectHandler;
use Tpay\Magento2\Service\TpayAliasServiceInterface;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Validator\AdditionalPaymentInfoValidator;

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

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var TpayAliasServiceInterface */
    private $aliasService;

    public function __construct(
        TpayInterface $tpayModel,
        TpayConfigInterface $tpayConfig,
        TpayService $tpayService,
        Session $checkoutSession,
        TransactionApiFacade $transactionApiFacade,
        RedirectHandler $redirectFactory,
        AdditionalPaymentInfoValidator $additionalPaymentInfoValidator,
        \Magento\Customer\Model\Session $customerSession,
        TpayAliasServiceInterface $aliasService
    ) {
        $this->tpay = $tpayModel;
        $this->tpayConfig = $tpayConfig;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->transaction = $transactionApiFacade;
        $this->redirectFactory = $redirectFactory;
        $this->additionalPaymentInfoValidator = $additionalPaymentInfoValidator;
        $this->customerSession = $customerSession;
        $this->aliasService = $aliasService;
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

            if (!isset($transaction['title'])) {
                return $this->redirectFactory->redirectError();
            }

            $this->handleOpenApiTrId($paymentData, $transaction);

            $this->tpayService->addCommentToHistory($orderId, 'Transaction title '.$transaction['title']);
            $transactionUrl = $transaction['transactionPaymentUrl'] ?? '';

            if (true === $this->tpayConfig->redirectToChannel()) {
                $transactionUrl = str_replace('gtitle', 'title', $transactionUrl);
            }

            $this->tpayService->addCommentToHistory($orderId, 'Transaction link '.$transactionUrl);
            $paymentData['additional_information']['transaction_url'] = $transactionUrl;
            $payment->setData($paymentData);
            $this->tpayService->saveOrderPayment($payment);

            if ($this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInformation) && $this->tpay->checkBlikLevel0Settings()) {
                if (isset($transaction['payments']['errors']) && count($transaction['payments']['errors']) > 0) {
                    return $this->redirectFactory->redirectError();
                }

                return $this->redirectFactory->redirectSuccess();
            }

            if (!empty($transactionUrl)) {
                return $this->redirectFactory->redirectTransaction($transactionUrl);
            }
        }

        return $this->redirectFactory->redirectError();
    }

    /**
     * Send BLIK code for transaction id
     *
     * @param string $blikTransactionId
     * @param string $blikCode
     * @param string $blikAlias
     */
    protected function blikPay($blikTransactionId, $blikCode, $blikAlias): bool
    {
        $apiResult = $this->transaction->blik($blikTransactionId, $blikCode, $blikAlias);

        return isset($apiResult['result']) && 1 === $apiResult['result'];
    }

    private function prepareTransaction($orderId, array $additionalPaymentInformation)
    {
        $data = $this->tpay->getTpayFormData($orderId);

        if ($this->additionalPaymentInfoValidator->validateBlikIfPresent($additionalPaymentInformation)) {
            $data['group'] = TransactionOriginApi::BLIK_CHANNEL;
            $data['channel'] = null;
            $this->handleBlikData($data, $additionalPaymentInformation['blik_code'] ?? '', $additionalPaymentInformation['blik_alias'] ?? false);
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

    private function handleBlikData(array &$data, string $blikCode, bool $blikAlias)
    {
        if ($this->transaction->isOpenApiUse() && $this->tpay->checkBlikLevel0Settings()) {
            if ($blikCode) {
                $data['blikPaymentData']['blikToken'] = $blikCode;
            }

            $customerId = $this->customerSession->getCustomerId();

            if ($customerId) {
                $alias = $blikAlias
                    ? $this->aliasService->getCustomerAlias($customerId)
                    : $this->buildBlikAlias($customerId);

                $data['blikPaymentData']['aliases'] = [
                    'type' => 'UID',
                    'value' => $alias,
                    'label' => 'tpay-magento2',
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

    private function buildBlikAlias(string $customerId): string
    {
        return sprintf('%s-%s', ConfigurationProvider::generateRandomString(10), $customerId);
    }
}
