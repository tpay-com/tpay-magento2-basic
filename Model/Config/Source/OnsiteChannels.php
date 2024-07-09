<?php

namespace Tpay\Magento2\Model\Config\Source;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tpay\Magento2\Api\TpayConfigInterface;
use Tpay\Magento2\Model\ApiFacade\Transaction\Dto\Channel;
use Tpay\Magento2\Model\ApiFacade\Transaction\TransactionApiFacade;

class OnsiteChannels implements OptionSourceInterface
{
    /** @var TransactionApiFacade */
    private $transactions;

    public function __construct(TpayConfigInterface $tpay, Context $context, StoreManagerInterface $storeManager, CacheInterface $cache)
    {
        $this->transactions = new TransactionApiFacade($tpay, $cache, $this->getStoreId($context, $storeManager));
    }

    public function getLabelFromValue(int $value): ?string
    {
        foreach ($this->toOptionArray() as $option) {
            if ($option['value'] === $value) {
                return $option['label'];
            }
        }

        return null;
    }

    /** @return array{array{value: int, label: string}} */
    public function toOptionArray(): array
    {
        return array_map(function (Channel $channel) {
            return ['value' => $channel->id, 'label' => $channel->fullName];
        }, $this->transactions->channels());
    }

    private function getStoreId(Context $context, StoreManagerInterface $storeManager): int
    {
        $scope = $context->getRequest()->getParam('store', null);
        $websiteScope = $context->getRequest()->getParam('website', null);
        $storeId = 0;
        if ($scope !== null) {
            $storeId = $storeManager->getStore($scope)->getId();
        } elseif ($websiteScope !== null) {
            $website = $storeManager->getWebsite($websiteScope);
            $storeId = $website->getDefaultStore()->getId();
        }

        return (int) $storeId;
    }
}
