<?php

declare(strict_types=1);

namespace Tpay\Magento2\Service;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;

class RedirectHandler
{
    /** @var RedirectFactory */
    protected $redirectFactory;

    /** @var UrlInterface */
    protected $builderInterface;

    public function __construct(RedirectFactory $redirectFactory, UrlInterface $builderInterface)
    {
        $this->redirectFactory = $redirectFactory;
        $this->builderInterface = $builderInterface;
    }

    public function redirectCreate(): ResultInterface
    {
        return $this->redirect('magento2basic/tpay/Create');
    }

    public function redirectCardPayment(): ResultInterface
    {
        return $this->redirect('magento2basic/tpay/CardPayment');
    }

    public function redirectCheckoutCart(): ResultInterface
    {
        return $this->redirect('checkout/cart');
    }

    public function redirectFailure(): ResultInterface
    {
        return $this->redirect('checkout/onepage/failure');
    }

    public function redirectError(): ResultInterface
    {
        return $this->redirect('magento2basic/tpay/error');
    }

    public function redirectCheckoutSuccess(): ResultInterface
    {
        return $this->redirect('checkout/onepage/success');
    }

    public function redirectSuccess(): ResultInterface
    {
        return $this->redirect('magento2basic/tpay/success');
    }

    public function redirectTransaction(string $transactionUrl): ResultInterface
    {
        return $this->redirect($transactionUrl);
    }

    private function redirect(string $path): ResultInterface
    {
        return $this->redirectFactory->create()->setPath($this->builderInterface->getUrl($path));
    }
}
