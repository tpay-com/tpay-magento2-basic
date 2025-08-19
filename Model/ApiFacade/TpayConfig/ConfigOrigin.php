<?php

namespace Tpay\Magento2\Model\ApiFacade\TpayConfig;

use Exception;
use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Repository;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\ApiFacade\CardTransaction\CardOrigin;
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

    /** @var CardOrigin */
    private $cardOrigin;

    /** @var CspNonceProvider */
    private $nonceProvider;

    /** @var Escaper */
    private $escaper;

    public function __construct(
        TpayInterface $tpay,
        TpayConfigInterface $tpayConfig,
        Repository $assetRepository,
        TpayTokensService $tokensService,
        CardOrigin $cardOrigin,
        CspNonceProvider $nonceProvider,
        Escaper $escaper
    ) {
        $this->tpay = $tpay;
        $this->tpayConfig = $tpayConfig;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
        $this->cardOrigin = $cardOrigin;
        $this->nonceProvider = $nonceProvider;
        $this->escaper = $escaper;
    }

    public function getConfig(): array
    {
        return [];
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
        return <<<EOD
            <script nonce='{$this->nonceProvider->generateNonce()}'>
                require(['jquery'], function ($) {
                    let script = document.createElement('script');
                    script.nonce = '{$this->nonceProvider->generateNonce()}';
                    script.textContent = '{$this->escaper->escapeJs($this->assetRepository->createAsset($script)->getContent())}';
                    document.head.appendChild(script);
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
        try {
            $check = $this->cardOrigin->requests($this->cardOrigin->getApiUrl().$this->tpayConfig->getCardApiKey(), ['api_password' => $this->tpayConfig->getCardApiPassword(), 'method' => 'check']);
            if (!isset($check['result']) || 1 !== $check['result']) {
                return [];
            }
        } catch (Exception $exception) {
            return [];
        }

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
                    'tpayCardsLogoUrl' => $this->tpayConfig->getCardImageUrl(),
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
