<?php

declare(strict_types=1);

namespace Tpay\Magento2\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Tpay\Magento2\Model\AdminWarningProvider;

class Warning extends Field
{
    private $warningProvider;

    public function __construct(
        Context $context,
        AdminWarningProvider $warningProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->warningProvider = $warningProvider;
    }

    public function shouldShowWarning(): bool
    {
        return $this->warningProvider->shouldShow();
    }

    public function getResolvedIp(): ?string
    {
        return $this->warningProvider->getIp();
    }

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _prepareLayout()
    {
        $this->setTemplate('Tpay_Magento2::system/config/warning.phtml');

        return parent::_prepareLayout();
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
