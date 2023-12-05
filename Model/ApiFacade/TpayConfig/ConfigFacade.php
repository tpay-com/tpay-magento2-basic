<?php
/**
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Framework\View\Asset\Repository;
use tpaycom\magento2basic\Api\TpayInterface;

/**
 * Class ConfigFacade
 * @package tpaycom\magento2basic\Model\ApiFacade\TpayConfig
 */
class ConfigFacade
{
    /** @var ConfigOrigin */
    private $originApi;

    /** @var ConfigOpen */
    private $openApi;

    /** @var bool */
    private $useOpenApi;

    public function __construct(TpayInterface $tpay, Repository $assetRepository)
    {
        $this->tpay = $tpay;
        $this->originApi = new ConfigOrigin($tpay, $assetRepository);
        $this->createOpenApiInstance($tpay, $assetRepository);
    }

    public function getConfig(): array
    {
        return $this->getCurrentApi()->getConfig();
    }

    private function getCurrentApi()
    {
        return $this->useOpenApi ? $this->openApi : $this->originApi;
    }

    private function createOpenApiInstance(TpayInterface $tpay, Repository $assetRepository)
    {
        try {
            $this->openApi = new ConfigOpen($tpay, $assetRepository);
            $this->useOpenApi = true;
        } catch (Exception $exception) {
            $this->openApi = null;
            $this->useOpenApi = false;
        }
    }
}
