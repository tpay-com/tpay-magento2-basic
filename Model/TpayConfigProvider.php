<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\TpayConfig\ConfigFacade;

class TpayConfigProvider implements ConfigProviderInterface
{
    /** @var Repository */
    protected $assetRepository;

    /** @var PaymentHelper */
    protected $paymentHelper;

    /** @var TpayInterface */
    protected $paymentMethod;

    /** @var ConfigFacade */
    protected $configFacade;

    public function __construct(PaymentHelper $paymentHelper, Repository $assetRepository)
    {
        $this->assetRepository = $assetRepository;
        $this->paymentHelper = $paymentHelper;
        $this->configFacade = new ConfigFacade($this->getPaymentMethodInstance(), $assetRepository);
    }

    public function getConfig()
    {
        return $this->configFacade->getConfig();
    }

    /** @return MethodInterface|TpayInterface */
    private function getPaymentMethodInstance()
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->paymentHelper->getMethodInstance(TpayInterface::CODE);
        }

        return $this->paymentMethod;
    }
}
