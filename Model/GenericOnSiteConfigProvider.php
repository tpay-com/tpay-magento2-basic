<?php

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Store\Model\ScopeInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionApiFacade;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;

#[\AllowDynamicProperties]
class GenericOnSiteConfigProvider implements ConfigProviderInterface
{
    protected $paymentMethod;

    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepository,
        MethodList $methods,
        ScopeConfigInterface $scopeConfig,
        TransactionApiFacade $transactionApiFacade
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->assetRepository = $assetRepository;
        $this->methodList = $methods;
        $this->scopeConfig = $scopeConfig;
        $this->transactionApiFacade = $transactionApiFacade;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $tpay = $this->getPaymentMethodInstance();
        $onsites = explode(',', $this->scopeConfig->getValue('payment/tpaycom_magento2basic/onsite_channels', ScopeInterface::SCOPE_STORE));

        $config = [
            'tpay' => [
                'payment' => [
                    'redirectUrl' => $tpay->getPaymentRedirectUrl(),
                    'tpayLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/logo_tpay.png'),
                    'merchantId' => $tpay->getMerchantId(),
                    'showPaymentChannels' => $this->showChannels(),
                    'getTerms' => $this->getTerms(),
                    'addCSS' => $this->createCSS('tpaycom_magento2basic::css/tpay.css'),
                    'blikStatus' => $this->getPaymentMethodInstance()->checkBlikLevel0Settings(),
                    'onlyOnlineChannels' => $this->getPaymentMethodInstance()->onlyOnlineChannels(),
                    'getBlikChannelID' => TransactionOriginApi::BLIK_CHANNEL,
                    'isInstallmentsAmountValid' => $this->getPaymentMethodInstance()->getInstallmentsAmountValid(),
                ],
            ],
        ];

        $channels = $this->transactionApiFacade->channels();

        foreach ($channels as $channel) {
            $config['generic'][$channel['id']] = [
                'id' => $channel['id'],
                'name' => $channel['fullName'],
                'logoUrl' => $channel['image']['url'],
            ];
        }


        return $config;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function generateURL($name)
    {
        return $this->assetRepository->createAsset($name)->getUrl();
    }

    /** @return null|string */
    public function showChannels()
    {
        $script = 'tpaycom_magento2basic::js/render_channels.js';

        return $this->createScript($script);
    }

    /**
     * @param string $script
     *
     * @return string
     */
    public function createScript($script)
    {
        return "
            <script type=\"text/javascript\">
                require(['jquery'], function ($) {
                    $.getScript('{$this->generateURL($script)}');

                });
            </script>";
    }

    /** @return null|string */
    public function getTerms()
    {
        return $this->getPaymentMethodInstance()->getTermsURL();
    }

    /**
     * @param string $css
     *
     * @return string
     */
    public function createCSS($css)
    {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->generateURL($css)}\">";
    }

    /** @return MethodInterface|TpayInterface */
    protected function getPaymentMethodInstance()
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->paymentHelper->getMethodInstance(TpayInterface::CODE);
        }

        return $this->paymentMethod;
    }
}
