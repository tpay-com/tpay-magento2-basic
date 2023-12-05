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

    public function __construct(TpayInterface $tpay, Repository $assetRepository)
    {
        $this->tpay = $tpay;
        $this->assetRepository = $assetRepository;
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
}
