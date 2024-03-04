<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Error implements ActionInterface
{
    /** @var ManagerInterface */
    private $messageManager;

    /** @var RedirectFactory */
    private $redirectFactory;

    public function __construct(ManagerInterface $messageManager, RedirectFactory $redirectFactory)
    {
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute(): ResultInterface
    {
        $this->messageManager->addWarningMessage(__('There was an error during your payment.'));

        return $this->redirectFactory->create()->setPath('checkout/onepage/failure');
    }
}
