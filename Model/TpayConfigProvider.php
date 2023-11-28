<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;

class TpayConfigProvider implements ConfigProviderInterface
{
    /** @var Repository */
    protected $assetRepository;

    /** @var PaymentHelper */
    protected $paymentHelper;

    /** @var TpayInterface */
    protected $paymentMethod;

    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepository
    ) {
        $this->assetRepository = $assetRepository;
        $this->paymentHelper = $paymentHelper;
    }

    public function getConfig()
    {
        $tpay = $this->getPaymentMethodInstance();

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
                    'useSandbox' => $tpay->useSandboxMode(),
                    'grandTotal' => number_format($this->getPaymentMethodInstance()->getCheckoutTotal(), 2, '.', ''),
                ],
            ],
        ];

        return $tpay->isAvailable() ? $config : [];
    }

    public function generateURL(string $name): string
    {
        return $this->assetRepository->createAsset($name)->getUrl();
    }

    public function showChannels(): ?string
    {
        $script = 'tpaycom_magento2basic::js/render_channels.js';

        return $this->createScript($script);
    }

    public function createScript(string $script): string
    {
        return "
            <script type=\"text/javascript\">
                require(['jquery'], function ($) {
                    $.getScript('{$this->generateURL($script)}');

                });
            </script>";
    }

    public function getTerms(): ?string
    {
        return $this->getPaymentMethodInstance()->getTermsURL();
    }

    public function createCSS(string $css): string
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
