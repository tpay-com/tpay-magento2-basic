<?php

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\Notifications\BasicNotificationHandler;

class NotificationModel extends BasicNotificationHandler
{
    /**
     * @param int    $merchantId
     * @param string $merchantSecret
     */
    public function __construct($merchantId, $merchantSecret)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
    }
}
