<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model\ApiFacade\Refund;

use Exception;
use Magento\Payment\Model\InfoInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\OpenApi;

/**
 * Class RefundApiFacade
 * @package tpaycom\magento2basic\Model\ApiFacade
 */
class RefundApiFacade
{
    /** @var RefundOriginApi */
    private $originApi;

    /** @var OpenApi */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay)
    {
        $this->originApi = new RefundOriginApi($tpay->getApiPassword(), $tpay->getApiKey(), $tpay->getMerchantId(), $tpay->getSecurityCode(), !$tpay->useSandboxMode());
        $this->createOpenApiInstance($tpay->getClientId(), $tpay->getOpenApiPassword(), !$tpay->useSandboxMode());
    }

    public function makeRefund(InfoInterface $payment, float $amount)
    {
        if ($payment->getAdditionalInformation('transaction_id')) {
            return $this->getCurrentApi()->makeRefund($payment, $amount);
        }

        return $this->originApi->makeRefund($payment, $amount);
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOpenApiInstance(string $clientId, string $apiPassword, bool $isProd)
    {
        try {
            $this->openApi = new OpenApi($clientId, $apiPassword, $isProd);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
