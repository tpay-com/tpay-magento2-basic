<?php

namespace Tpay\Magento2\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View;
use Tpay\Magento2\Model\TpayPayment;

class AddSendReminderButtonInOrderView
{
    public function beforeSetLayout(View $subject)
    {
        if (!$subject->getAuthorization()->isAllowed('Tpay_Magento2::send_reminder')) {
            return;
        }
        $order = $subject->getOrder();
        if (null === $order) {
            return;
        }
        if (TpayPayment::ORDER_STATUS_PENDING !== $order->getStatus()) {
            return;
        }
        $payment = $order->getPayment();
        if (null === $payment) {
            return;
        }
        $info = $payment->getAdditionalInformation();
        if (empty($info['transaction_url'])) {
            return;
        }

        $subject->addButton(
            'send_tpay_reminder',
            [
                'label' => __('Send Payment Reminder'),
                'onclick' => "setLocation('".$subject->getUrl('tpay/order/reminder')."')",
                'class' => 'secondary',
            ]
        );
    }
}
