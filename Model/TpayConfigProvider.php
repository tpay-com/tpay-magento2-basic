<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\TpayConfig\ConfigFacade;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Service\TpayService;
use Tpay\Magento2\Service\TpayTokensService;

class TpayConfigProvider implements ConfigProviderInterface
{
    /** @var PaymentHelper */
    protected $paymentHelper;

    /** @var TpayInterface */
    protected $paymentMethod;

    /** @var ConfigFacade */
    protected $configFacade;

    /** @var TransactionApiFacade */
    protected $transactionApi;

    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepository,
        TpayTokensService $tokensService,
        TransactionApiFacade $transactionApiFacade,
        TpayService $tpayService,
        TpayConfigInterface $tpayConfig
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->transactionApi = $transactionApiFacade;
        $this->configFacade = new ConfigFacade($this->getPaymentMethodInstance(), $tpayConfig, $assetRepository, $tokensService, $tpayService);
    }

    public function getConfig(): array
    {
        if (!$this->paymentMethod->isAvailable()) {
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
