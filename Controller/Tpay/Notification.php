<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Psr\Log\LoggerInterface;
use Throwable;
use Tpay\Magento2\Notification\NotificationProcessor;

class Notification implements CsrfAwareActionInterface, HttpPostActionInterface
{
    public const BAD_REQUEST = 400;
    public const HTTP_OK = 200;

    /** @var NotificationProcessor */
    protected $notificationProcessor;

    /** @var RawFactory */
    private $resultPageFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RawFactory $resultPageFactory,
        NotificationProcessor $notificationProcessor,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger = $logger;
    }

    public function execute(): Raw
    {
        $result = $this->resultPageFactory->create();
        try {
            $this->notificationProcessor->process();
        } catch (Throwable $e) {
            $this->logger->info('Failed to process Tpay notification: '.$e->getMessage(), ['exception' => $e]);

            return $result
                ->setHttpResponseCode(self::BAD_REQUEST)
                ->setContents('FALSE - '.$e->getMessage());
        }

        return $result
            ->setHttpResponseCode(self::HTTP_OK)
            ->setContents('TRUE');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
