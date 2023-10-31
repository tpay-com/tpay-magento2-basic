<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Model\RefundModel;
use tpayLibs\src\_class_tpay\Utilities\Util;

class Refund
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $apiPassword;

    /** @var int */
    protected $merchantId;

    /** @var string */
    protected $merchantSecret;

    /** @throws \Exception */
    public function makeRefund(InfoInterface $payment, float $amount): bool
    {
        Util::$loggingEnabled = false;
        $RefundModel = new RefundModel($this->apiPassword, $this->apiKey, $this->merchantId, $this->merchantSecret);

        /** @var array{result?: string, err?: int} $apiResult */
        $apiResult = $RefundModel
            ->setTransactionID($payment->getParentTransactionId())
            ->refundAny(number_format($amount, 2));

        if (array_key_exists('result', $apiResult) && 1 === (int) $apiResult['result']) {
            return true;
        }
        $errCode = array_key_exists('err', $apiResult) ? ' error code: '.$apiResult['err'] : '';
        throw new Exception(__('Payment refunding error. -'.$errCode));
    }

    public function setMerchantSecret(string $merchantSecret): self
    {
        $this->merchantSecret = $merchantSecret;

        return $this;
    }

    public function setMerchantId(int $merchantId): self
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    public function setApiPassword(string $apiPassword): self
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
