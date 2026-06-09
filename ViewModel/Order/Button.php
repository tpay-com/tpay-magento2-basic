<?php

namespace Tpay\Magento2\ViewModel\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Tpay\Magento2\Model\TpayPayment;

class Button implements ArgumentInterface
{
    /** @var Registry */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function shouldShowButton(): bool
    {
        if (TpayPayment::ORDER_STATUS_PENDING !== $this->getOrder()->getStatus()) {
            return false;
        }

        return !empty($this->getPaymentUrl());
    }

    public function getPaymentUrl(): ?string
    {
        $payment = $this->getOrder()->getPayment();
        if (null === $payment) {
            return null;
        }
        $info = $payment->getAdditionalInformation();

        return $info['transaction_url'] ?? null;
    }

    private function getOrder(): OrderInterface
    {
        return $this->registry->registry('current_order');
    }
}
