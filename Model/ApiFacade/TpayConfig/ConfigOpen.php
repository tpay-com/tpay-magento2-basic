<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Magento\Framework\View\Asset\Repository;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionOriginApi;
use Tpay\Magento2\Service\TpayTokensService;
use tpaySDK\Api\TpayApi;

class ConfigOpen extends TpayApi
{
    /** @var TpayTokensService */
    protected $tokensService;

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayConfigInterface */
    private $tpayConfig;

    /** @var Repository */
    private $assetRepository;

    public function __construct(TpayInterface $tpay, TpayConfigInterface $tpayConfig, Repository $assetRepository, TpayTokensService $tokensService)
    {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
        parent::__construct($tpayConfig->getOpenApiClientId(), $tpayConfig->getOpenApiPassword(), !$tpayConfig->useSandboxMode());
    }

    public function getConfig(): array
    {
        return [
            'tpay' => [
                'payment' => [
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'tpayLogoUrl' => $this->generateURL('Tpay_Magento2::images/logo_tpay.png'),
                    'tpayCardsLogoUrl' => $this->generateURL('Tpay_Magento2::images/card.svg'),
                    'showPaymentChannels' => $this->showChannels(),
                    'getTerms' => $this->getTerms(),
                    'getRegulations' => $this->getRegulations(),
                    'addCSS' => $this->createCSS('Tpay_Magento2::css/tpay.css'),
                    'blikStatus' => $this->tpay->checkBlikLevel0Settings(),
                    'getBlikChannelID' => TransactionOriginApi::BLIK_CHANNEL,
                    'useSandbox' => $this->tpayConfig->useSandboxMode(),
                    'grandTotal' => number_format($this->tpay->getCheckoutTotal(), 2, '.', ''),
                    'groups' => $this->transactions()->getBankGroups((bool) $this->tpayConfig->onlyOnlineChannels())['groups'],
                ],
            ],
        ];
    }

    public function generateURL(string $name): string
    {
        return $this->assetRepository->createAsset($name)->getUrl();
    }

    public function showChannels(): ?string
    {
        $script = 'Tpay_Magento2::js/open_render_channels.js';

        return $this->createScript($script);
    }

    public function createScript(string $script): string
    {
        return <<<EOD

            <script type="text/javascript">
                require(['jquery'], function ($) {
                    $.getScript('{$this->generateURL($script)}');

                });
            </script>
EOD;
    }

    public function getTerms(): ?string
    {
        return $this->tpayConfig->getTermsURL();
    }

    public function getRegulations(): ?string
    {
        return $this->tpayConfig->getRegulationsURL();
    }

    public function createCSS(string $css): string
    {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->generateURL($css)}\">";
    }

    public function getCardConfig()
    {
        $customerTokensData = [];

        if ($this->tpayConfig->getCardSaveEnabled() && $this->tpay->isCustomerLoggedIn()) {
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
                    'getRegulations' => $this->getRegulations(),
                ],
            ],
        ];
    }

    public function fetchJavaScripts()
    {
        $script = [];
        $script[] = 'Tpay_Magento2::js/jquery.payment.min.js';
        $script[] = 'Tpay_Magento2::js/jsencrypt.min.js';
        $script[] = 'Tpay_Magento2::js/string_routines.js';
        $script[] = 'Tpay_Magento2::js/tpayCards.js';
        $script[] = 'Tpay_Magento2::js/renderSavedCards.js';
        $scripts = '';

        foreach ($script as $key => $value) {
            $scripts .= $this->createScript($value);
        }

        return $scripts;
    }
}
