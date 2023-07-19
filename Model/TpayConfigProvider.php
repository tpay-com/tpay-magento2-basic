<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Model\MethodInterface;
use tpaycom\magento2basic\Api\TpayInterface;

use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class TpayConfigProvider
 *
 * @package tpaycom\magento2basic\Model
 */
class TpayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Repository
     */
    protected $assetRepository;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var TpayInterface
     */
    protected $paymentMethod;

    /**
     * TpayConfigProvider constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param Repository $assetRepository
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepository
    ) {
        $this->assetRepository = $assetRepository;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritdoc}
     */
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
                    'getBlikChannelID' => TransactionModel::BLIK_CHANNEL,
                    'isInstallmentsAmountValid' => $this->getPaymentMethodInstance()->getInstallmentsAmountValid(),
                    'useSandbox' => $tpay->useSandboxMode()
                ],
            ],
        ];

        return $tpay->isAvailable() ? $config : [];
    }

    /**
     * @return TpayInterface|MethodInterface
     */
    protected function getPaymentMethodInstance()
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->paymentHelper->getMethodInstance(TpayInterface::CODE);
        }

        return $this->paymentMethod;
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

    /**
     * @return string|null
     */
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

    /**
     * @return string|null
     */
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
}
