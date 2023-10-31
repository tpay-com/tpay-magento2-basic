<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\Notifications\BasicNotificationHandler;

class NotificationModel extends BasicNotificationHandler
{
    public function __construct(int $merchantId, string $merchantSecret)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
    }
}
