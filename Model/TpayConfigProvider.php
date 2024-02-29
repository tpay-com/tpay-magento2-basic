<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use tpaycom\magento2basic\Api\TpayConfigInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\TpayConfig\ConfigFacade;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use tpaycom\magento2basic\Service\TpayTokensService;

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
        StoreManagerInterface $storeManager,
        TpayTokensService $tokensService,
        TransactionApiFacade $transactionApiFacade,
        TpayConfigInterface $tpayConfig
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->transactionApi = $transactionApiFacade;
        $this->configFacade = new ConfigFacade($this->getPaymentMethodInstance(), $tpayConfig, $assetRepository, $tokensService, $storeManager);
    }

    public function getConfig(): array
    {
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
