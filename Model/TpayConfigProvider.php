<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\TpayConfig\ConfigFacade;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;

class TpayConfigProvider implements ConfigProviderInterface
{
    /** @var PaymentHelper */
    protected $paymentHelper;

    /** @var TpayInterface */
    protected $paymentMethod;

    /** @var ConfigFacade\Proxy */
    protected $configFacade;

    /** @var TransactionApiFacade */
    protected $transactionApi;

    public function __construct(
        PaymentHelper $paymentHelper,
        TransactionApiFacade $transactionApiFacade,
        ConfigFacade\Proxy $configFacade
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->transactionApi = $transactionApiFacade;
        $this->configFacade = $configFacade;
    }

    public function getConfig(): array
    {
        if (!$this->getPaymentMethodInstance()->isAvailable()) {
            return [];
        }

        $config = $this->configFacade->getConfig();
        $channels = $this->transactionApi->channels();

        foreach ($channels as $channel) {
            $config['generic'][$channel->id] = [
                'id' => $channel->id,
                'name' => $channel->fullName,
                'logoUrl' => $channel->image,
            ];
        }

        return $config;
    }

    private function getPaymentMethodInstance(): TpayInterface
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->paymentHelper->getMethodInstance(TpayInterface::CODE);
        }

        return $this->paymentMethod;
    }
}
