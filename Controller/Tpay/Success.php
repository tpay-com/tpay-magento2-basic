<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Tpay\Magento2\Service\RedirectHandler;

class Success implements ActionInterface
{
    /** @var RedirectHandler */
    private $redirectFactory;

    public function __construct(RedirectHandler $redirectFactory)
    {
        $this->redirectFactory = $redirectFactory;
    }

    public function execute(): ResultInterface
    {
        return $this->redirectFactory->redirectCheckoutSuccess();
    }
}
