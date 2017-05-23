<?php
/**
 *
 * @category    payment gateway
 * @package     Tpaycom_Magento2.1
 * @author      Tpay.com
 * @copyright   (https://tpay.com)
 */

namespace tpaycom\magento2basic\Block\Payment\tpay\Redirect;

use Magento\Framework\View\Element\Template;

/**
 * Class Form
 *
 * @package tpaycom\magento2basic\Block\Payment\tpay\Redirect
 */
class Form extends Template
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $tpayData = [];

    /**
     * @var array
     */
    protected $additionalInformation = [];

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return Form
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param array $tpayData
     *
     * @return Form
     */
    public function setTpayData(array $tpayData)
    {
        $this->tpayData = $tpayData;

        return $this;
    }

    /**
     * @return array
     */
    public function getTpayData()
    {
        return $this->tpayData;
    }

    /**
     * @param array $additionalInformation
     *
     * @return Form
     */
    public function setAdditionalInformation(array $additionalInformation)
    {
        $this->additionalInformation = array_filter($additionalInformation);

        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalInformation()
    {
        return $this->additionalInformation;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->setTemplate('tpaycom_magento2basic::redirect/form.phtml');

        parent::_construct();
    }
}
