<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Payment\Helper\Data as PaymentHelper;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\TpayConfig\ConfigFacade;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;
use Tpay\Magento2\Service\TpayAliasServiceInterface;

class TpayConfigProvider implements ConfigProviderInterface
{
    public const CACHE_TAG = 'TPAY_CONFIG';

    /** @var PaymentHelper */
    protected $paymentHelper;

    /** @var TpayInterface */
    protected $paymentMethod;

    /** @var ConfigFacade */
    protected $configFacade;

    /** @var TransactionApiFacade */
    protected $transactionApi;

    /** @var Session */
    protected $customerSession;

    /** @var TpayAliasServiceInterface */
    protected $aliasService;

    public function __construct(
        PaymentHelper $paymentHelper,
        TransactionApiFacade $transactionApiFacade,
        Session $customerSession,
        TpayAliasServiceInterface $aliasService,
        ConfigFacade $configFacade
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->transactionApi = $transactionApiFacade;
        $this->customerSession = $customerSession;
        $this->aliasService = $aliasService;
        $this->configFacade = $configFacade;
    }

    public function getConfig(): array
    {
        $config = $this->configFacade->getConfig();
        $channels = $this->transactionApi->channels();

        if ($this->customerSession->getCustomerId()) {
            $alias = $this->aliasService->getCustomerAlias((int) $this->customerSession->getCustomerId());

            if ($alias) {
                $config['blik_alias'] = true;
            }
        }

        foreach ($channels as $channel) {
            $config['generic'][$channel->id] = [
                'id' => $channel->id,
                'name' => $channel->fullName,
                'logoUrl' => $channel->image,
            ];
        }

        return $config;
    }
}
