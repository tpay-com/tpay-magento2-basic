<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.3
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Model;

use tpayLibs\src\_class_tpay\Notifications\BasicNotificationHandler;

/**
 * Class Notification
 *
 * @package tpaycom\magento2basic\Model
 */
class NotificationModel extends BasicNotificationHandler
{
    /**
     * @param int $merchantId
     * @param string $merchantSecret
     */
    public function __construct($merchantId, $merchantSecret)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        parent::__construct();
    }

}
