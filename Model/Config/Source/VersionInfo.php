<?php

namespace Tpay\Magento2\Model\Config\Source;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Tpay\Magento2\Api\TpayConfigInterface;

class VersionInfo extends Field
{
    /** @var TpayConfigInterface */
    private $tpayConfig;

    public function __construct(Context $context, TpayConfigInterface $tpayConfig, array $data = [])
    {
        $this->tpayConfig = $tpayConfig;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->tpayConfig->buildMagentoInfo();
    }
}
