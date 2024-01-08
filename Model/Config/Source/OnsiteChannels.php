<?php

namespace tpaycom\magento2basic\Model\Config\Source;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Data\OptionSourceInterface;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\Transaction\TransactionApiFacade;

class OnsiteChannels implements OptionSourceInterface
{
    /** @var TransactionApiFacade */
    private $transactions;

    public function __construct(TpayInterface $tpay, CacheInterface $cache)
    {
        $this->transactions = new TransactionApiFacade($tpay, $cache);
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
        return array_map(function (array $channel) {
            return ['value' => (int) $channel['id'], 'label' => $channel['fullName']];
        }, $this->transactions->channels());
    }
}
