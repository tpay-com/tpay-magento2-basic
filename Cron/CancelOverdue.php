<?php

namespace Tpay\Magento2\Cron;

use DateInterval;
use DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Tpay\Magento2\Api\TpayInterface;
use Tpay\Magento2\Model\Sales\OrderRepository;

class CancelOverdue
{
    public const XML_CONFIG_PATH_ENABLE = 'payment/tpaycom_magento2basic/cancel/active';
    public const XML_CONFIG_PATH_DAYS = 'payment/tpaycom_magento2basic/cancel/days';

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ScopeConfigInterface */
    private $config;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        OrderRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        foreach ($this->storeManager->getStores() as $store) {
            if (!$this->config->isSetFlag(self::XML_CONFIG_PATH_ENABLE)) {
                continue;
            }

            $days = $this->config->getValue(self::XML_CONFIG_PATH_DAYS, 'store', $store->getId());

            $date = new DateTime();
            $date->sub(new DateInterval('P'.$days.'D'));
            $initialDate = $date->format('Y-m-d');

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('created_at', ['lt' => $initialDate]);
            $collection->addFieldToFilter('store_id', ['eq' => $store->getId()]);
            $collection->addFieldToFilter('state', ['eq' => Order::STATE_PENDING_PAYMENT]);

            $collection->getSelect()->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id = payment.parent_id',
                ['method']
            )
                ->where('payment.method = ?', TpayInterface::CODE);

            /** @var list<Order> $items */
            $items = $collection->getItems();

            foreach ($items as $item) {
                $this->logger->info('Canceling order payment due to lack of payment', ['orderId' => $item->getId(), 'incrementId' => $item->getIncrementId()]);
                $item->cancel();
                $item->addCommentToStatusHistory(__('Cancelled automatically due to lack of payment'));
                $this->orderRepository->save($item);
            }
        }
    }
}
