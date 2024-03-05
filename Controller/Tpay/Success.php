<?php

declare(strict_types=1);

namespace TpayCom\Magento2Basic\Controller\Tpay;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Success implements ActionInterface
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
        $this->messageManager->addSuccessMessage(__('Thank you for your payment!'));

        return $this->redirectFactory->create()->setPath('checkout/onepage/success');
    }
}
