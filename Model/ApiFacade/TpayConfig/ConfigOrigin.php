<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Magento\Framework\View\Asset\Repository;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;
use Tpay\Magento2\Service\TpayTokensService;

class ConfigOrigin
{
    /** @var Repository */
    protected $assetRepository;

    /** @var TpayTokensService */
    protected $tokensService;

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
    }

    public function getConfig(): array
    {
        $config = [
            'tpay' => [
                'payment' => [
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'tpayLogoUrl' => $this->generateURL('Tpay_Magento2::images/logo_tpay.png'),
                    'tpayCardsLogoUrl' => $this->generateURL('Tpay_Magento2::images/card.svg'),
                    'merchantId' => $this->tpayConfig->getMerchantId(),
                    'showPaymentChannels' => $this->showChannels(),
                    'getTerms' => $this->getTerms(),
                    'addCSS' => $this->createCSS('Tpay_Magento2::css/tpay.css'),
                    'blikStatus' => $this->tpay->checkBlikLevel0Settings(),
                    'onlyOnlineChannels' => $this->tpayConfig->onlyOnlineChannels(),
                    'getBlikChannelID' => TransactionOriginApi::BLIK_CHANNEL,
                    'useSandbox' => $this->tpayConfig->useSandboxMode(),
                    'grandTotal' => number_format($this->tpay->getCheckoutTotal(), 2, '.', ''),
                ],
            ],
        ];

        return $this->tpay->isAvailable() ? $config : [];
    }

    public function generateURL(string $name): string
    {
        return $this->assetRepository->createAsset($name)->getUrl();
    }

    public function showChannels(): ?string
    {
        $script = 'Tpay_Magento2::js/render_channels.js';

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
        return $this->tpayConfig->getTermsURL();
    }

    public function createCSS(string $css): string
    {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->generateURL($css)}\">";
    }

    public function getCardConfig()
    {
        $customerTokensData = [];
        if ($this->tpayConfig->getCardSaveEnabled() && $this->tpay->isCustomerLoggedIn()) {
            $customerTokens = $this->tokensService->getCustomerTokens($this->tpay->getCheckoutCustomerId());
            foreach ($customerTokens as $key => $value) {
                $customerTokensData[] = [
                    'cardShortCode' => $value['cardShortCode'],
                    'id' => $value['tokenId'],
                    'vendor' => $value['vendor'],
                ];
            }
        }

        return [
            'tpaycards' => [
                'payment' => [
                    'tpayLogoUrl' => $this->generateURL('Tpay_Magento2::images/logo_tpay.png'),
                    'tpayCardsLogoUrl' => $this->generateURL('Tpay_Magento2::images/card.svg'),
                    'getTpayLoadingGif' => $this->generateURL('Tpay_Magento2::images/loading.gif'),
                    'getRSAkey' => $this->tpayConfig->getRSAKey(),
                    'fetchJavaScripts' => $this->fetchJavaScripts(),
                    'addCSS' => $this->createCSS('Tpay_Magento2::css/tpaycards.css'),
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'isCustomerLoggedIn' => $this->tpay->isCustomerLoggedIn(),
                    'customerTokens' => $customerTokensData,
                    'isSavingEnabled' => $this->tpayConfig->getCardSaveEnabled(),
                    'getTerms' => $this->getTerms(),
                ],
            ],
        ];
    }

    private function fetchJavaScripts(): string
    {
        $script = [];
        $script[] = 'Tpay_Magento2::js/jquery.payment.min.js';
        $script[] = 'Tpay_Magento2::js/jsencrypt.min.js';
        $script[] = 'Tpay_Magento2::js/string_routines.js';
        $script[] = 'Tpay_Magento2::js/tpayCards.js';
        $script[] = 'Tpay_Magento2::js/renderSavedCards.js';
        $scripts = '';
        foreach ($script as $value) {
            $scripts .= $this->createScript($value);
        }

        return $scripts;
    }
}
