<?php

namespace Tpay\Magento2\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Throwable;
use Tpay\Magento2\Model\TpayPayment;
use Tpay\Magento2\Service\ReminderService;

class Reminder extends Action
{
    /** @var ReminderService */
    private $reminderService;

    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(
        Context $context,
        ReminderService $reminderService,
        OrderRepository $orderRepository,
    ) {
        parent::__construct($context);
        $this->reminderService = $reminderService;
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));

        if (TpayPayment::ORDER_STATUS_PENDING !== $order->getStatus()) {
            $this->messageManager->addErrorMessage(__('Reminder allowed only for pending payments.'));

            return $this->redirectBack();
        }

        $payment = $order->getPayment();
        if (null === $payment) {
            $this->messageManager->addErrorMessage(__('No payment found for this order.'));

            return $this->redirectBack();
        }

        $info = $payment->getAdditionalInformation();
        if (empty($info['transaction_url'])) {
            $this->messageManager->addErrorMessage(__('Payment does not contain transaction url.'));

            return $this->redirectBack();
        }

        try {
            if ($this->reminderService->reminder($order)) {
                $this->messageManager->addSuccessMessage(__('Reminder has been sent.'));

                return $this->redirectBack();
            }
        } catch (Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $this->messageManager->addErrorMessage(__('Problem during sending reminder.'));

        return $this->redirectBack();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tpay_Magento2::send_reminder');
    }

    private function redirectBack()
    {
        return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $this->getRequest()->getParam('order_id')]);
    }
}
