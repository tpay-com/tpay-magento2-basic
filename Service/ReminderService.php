<?php

namespace Tpay\Magento2\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;

class ReminderService
{
    /** @var TransportBuilder */
    private $transportBuilder;

    /** @var StateInterface */
    private $inlineTranslation;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
    }

    public function reminder(OrderInterface $order)
    {
        $payment = $order->getPayment();
        if (null === $payment) {
            return false;
        }
        $info = $payment->getAdditionalInformation();
        if (null === $info) {
            return false;
        }
        if (empty($info['transaction_url'])) {
            return false;
        }
        $paymentUrl = $info['transaction_url'];

        $this->inlineTranslation->suspend();

        $templateId = $this->scopeConfig->getValue('payment/tpaycom_magento2basic/sale_settings/payment_remind_email_template', ScopeInterface::SCOPE_STORE, $order->getStoreId());
        if (empty($templateId)) {
            $templateId = 'payment_tpaycom_magento2basic_sale_settings_payment_remind_email_template';
        }

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $order->getStoreId(),
            ])
            ->setTemplateVars([
                'paymentUrl' => $paymentUrl,
                'order' => $order->getIncrementId(),
                'name' => $order->getCustomerName(),
            ])
            ->setFromByScope('general', $order->getStoreId())
            ->addTo($order->getCustomerEmail(), $order->getCustomerName())
            ->getTransport();

        $transport->sendMessage();
        $this->inlineTranslation->resume();

        $order->addCommentToStatusHistory(__('Payment reminder sent'));
        $this->orderRepository->save($order);

        return true;
    }
}
