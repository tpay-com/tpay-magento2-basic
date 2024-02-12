<?php

namespace tpaycom\magento2basic\Model\ApiFacade\TpayConfig;

use Magento\Framework\View\Asset\Repository;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;
use tpaycom\magento2basic\Service\TpayTokensService;
use tpaySDK\Api\TpayApi;

class ConfigOpen extends TpayApi
{
    /** @var TpayTokensService */
    protected $tokensService;

    /** @var TpayInterface */
    private $tpay;

    /** @var Repository */
    private $assetRepository;

    public function __construct(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService)
    {
        $this->tpay = $tpay;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
        parent::__construct($tpay->getOpenApiClientId(), $tpay->getOpenApiPassword(), !$tpay->useSandboxMode());
    }

    public function getConfig(): array
    {
        $config = [
            'tpay' => [
                'payment' => [
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'tpayLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/logo_tpay.png'),
                    'tpayCardsLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/card.svg'),
                    'showPaymentChannels' => $this->showChannels(),
                    'getTerms' => $this->getTerms(),
                    'addCSS' => $this->createCSS('tpaycom_magento2basic::css/tpay.css'),
                    'blikStatus' => $this->tpay->checkBlikLevel0Settings(),
                    'getBlikChannelID' => TransactionOriginApi::BLIK_CHANNEL,
                    'useSandbox' => $this->tpay->useSandboxMode(),
                    'grandTotal' => number_format($this->tpay->getCheckoutTotal(), 2, '.', ''),
                    'groups' => $this->transactions()->getBankGroups((bool) $this->tpay->onlyOnlineChannels())['groups'],
                ],
            ],
        ];

        return array_merge($config, $this->getCardConfig());
    }

    public function generateURL(string $name): string
    {
        return $this->assetRepository->createAsset($name)->getUrl();
    }

    public function showChannels(): ?string
    {
        $script = 'tpaycom_magento2basic::js/open_render_channels.js';

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
        return $this->tpay->getTermsURL();
    }

    public function createCSS(string $css): string
    {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->generateURL($css)}\">";
    }

    public function getCardConfig()
    {
        $customerTokensData = [];

        if ($this->tpay->getCardSaveEnabled() && $this->tpay->isCustomerLoggedIn()) {
            $customerTokens = $this->tokensService->getCustomerTokens($this->tpay->getCheckoutCustomerId(), true);
            foreach ($customerTokens as $value) {
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
                    'tpayLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/logo_tpay.png'),
                    'tpayCardsLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/card.svg'),
                    'getTpayLoadingGif' => $this->generateURL('tpaycom_magento2basic::images/loading.gif'),
                    'getRSAkey' => $this->tpay->getRSAKey(),
                    'fetchJavaScripts' => $this->fetchJavaScripts(),
                    'addCSS' => $this->createCSS('tpaycom_magento2basic::css/tpaycards.css'),
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'isCustomerLoggedIn' => $this->tpay->isCustomerLoggedIn(),
                    'customerTokens' => $customerTokensData,
                    'isSavingEnabled' => $this->tpay->getCardSaveEnabled(),
                ],
            ],
        ];
    }

    public function fetchJavaScripts()
    {
        $script = [];
        $script[] = 'tpaycom_magento2basic::js/jquery.payment.min.js';
        $script[] = 'tpaycom_magento2basic::js/jsencrypt.min.js';
        $script[] = 'tpaycom_magento2basic::js/string_routines.js';
        $script[] = 'tpaycom_magento2basic::js/tpayCards.js';
        $script[] = 'tpaycom_magento2basic::js/renderSavedCards.js';
        $script[] = 'tpaycom_magento2basic::js/tpayGeneric.js';
        $scripts = '';

        foreach ($script as $key => $value) {
            $scripts .= $this->createScript($value);
        }

        return $scripts;
    }
}
