<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model\ApiFacade\TpayConfig;

use Magento\Framework\View\Asset\Repository;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionOriginApi;
use tpaycom\magento2basic\Service\TpayTokensService;

/**
 * Class ConfigOrigin
 * @package tpaycom\magento2basic\Model\ApiFacade\TpayConfig
 */
class ConfigOrigin
{
    /** @var TpayInterface */
    private $tpay;

    /** @var Repository */
    protected $assetRepository;

    /** @var TpayTokensService */
    protected $tokensService;

    public function __construct(TpayInterface $tpay, Repository $assetRepository, TpayTokensService $tokensService)
    {
        $this->tpay = $tpay;
        $this->assetRepository = $assetRepository;
        $this->tokensService = $tokensService;
    }

    public function getConfig(): array
    {
        $config = [
            'tpay' => [
                'payment' => [
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'tpayLogoUrl' => $this->generateURL('tpaycom_magento2basic::images/logo_tpay.png'),
                    'merchantId' => $this->tpay->getMerchantId(),
                    'showPaymentChannels' => $this->showChannels(),
                    'getTerms' => $this->getTerms(),
                    'addCSS' => $this->createCSS('tpaycom_magento2basic::css/tpay.css'),
                    'blikStatus' => $this->tpay->checkBlikLevel0Settings(),
                    'onlyOnlineChannels' => $this->tpay->onlyOnlineChannels(),
                    'getBlikChannelID' => TransactionOriginApi::BLIK_CHANNEL,
                    'useSandbox' => $this->tpay->useSandboxMode(),
                    'grandTotal' => number_format($this->tpay->getCheckoutTotal(), 2, '.', ''),
                ],
            ],
        ];

        $config = array_merge($config, $this->getCardConfig());

        return $this->tpay->isAvailable() ? $config : [];
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
        return $this->tpay->getTermsURL();
    }

    public function createCSS(string $css): string
    {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$this->generateURL($css)}\">";
    }

    private function getCardConfig()
    {
        $customerTokensData = [];
        if ($this->tpay->getCardSaveEnabled()) {
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
                    'tpayLogoUrl' => $this->generateURL('tpaycom_magento2cards::images/logo_tpay.png'),
                    'getTpayLoadingGif' => $this->generateURL('tpaycom_magento2cards::images/loading.gif'),
                    'getRSAkey' => $this->tpay->getRSAKey(),
                    'fetchJavaScripts' => $this->fetchJavaScripts(),
                    'addCSS' => $this->createCSS('tpaycom_magento2cards::css/tpaycards.css'),
                    'redirectUrl' => $this->tpay->getPaymentRedirectUrl(),
                    'isCustomerLoggedIn' => $this->tpay->isCustomerLoggedIn(),
                    'customerTokens' => $customerTokensData,
                    'isSavingEnabled' => $this->tpay->getCardSaveEnabled(),
                ],
            ],
        ];
    }

    private function fetchJavaScripts(): string
    {
        $script = [];
        $script[] = 'tpaycom_magento2basic::js/jquery.payment.min.js';
        $script[] = 'tpaycom_magento2basic::js/jsencrypt.min.js';
        $script[] = 'tpaycom_magento2basic::js/string_routines.js';
        $script[] = 'tpaycom_magento2basic::js/tpayCards.js';
        $script[] = 'tpaycom_magento2basic::js/renderSavedCards.js';
        $scripts = '';
        foreach ($script as $value) {
            $scripts .= $this->createScript($value);
        }

        return $scripts;
    }
}
