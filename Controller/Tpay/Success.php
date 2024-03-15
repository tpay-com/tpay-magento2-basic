<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Tpay\Magento2\Service\RedirectHandler;

class Success implements ActionInterface
{
    /** @var ManagerInterface */
    private $messageManager;

    /** @var RedirectHandler */
    private $redirectFactory;

    public function __construct(ManagerInterface $messageManager, RedirectHandler $redirectFactory)
    {
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute(): ResultInterface
    {
        $this->messageManager->addSuccessMessage(__('Thank you for your payment!'));

        return $this->redirectFactory->redirectCheckoutSuccess();
    }
}
