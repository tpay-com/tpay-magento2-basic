<?php

declare(strict_types=1);

namespace Tpay\Magento2\Controller\Tpay;

use Laminas\Http\Response;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Tpay\Magento2\Notification\NotificationProcessor;

class Notification implements CsrfAwareActionInterface
{
    /** @var NotificationProcessor */
    protected $notificationProcessor;

    /** @var ResponseInterface */
    private $response;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ResponseInterface $response,
        NotificationProcessor $notificationProcessor,
        LoggerInterface $logger
    ) {
        $this->response = $response;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger = $logger;
    }

    public function execute(): ?Response
    {
        try {
            $this->notificationProcessor->process();
        } catch (Throwable $e) {
            $this->logger->info('Failed to process Tpay notification: ' . $e->getMessage(), ['exception' => $e]);
            return $this->response->setStatusCode(Response::STATUS_CODE_400)->setContent($e->getMessage());
        }

        return $this->response->setStatusCode(Response::STATUS_CODE_200)->setContent('TRUE');
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
