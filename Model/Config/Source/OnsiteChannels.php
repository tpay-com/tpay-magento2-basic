<?php

namespace TpayCom\Magento2Basic\Model\Config\Source;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Data\OptionSourceInterface;
use TpayCom\Magento2Basic\Api\TpayConfigInterface;
use TpayCom\Magento2Basic\Model\ApiFacade\Transaction\Dto\Channel;
use TpayCom\Magento2Basic\Model\ApiFacade\Transaction\TransactionApiFacade;

class OnsiteChannels implements OptionSourceInterface
{
    /** @var TransactionApiFacade */
    private $transactions;

    public function __construct(TpayConfigInterface $tpay, CacheInterface $cache)
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
        return array_map(function (Channel $channel) {
            return ['value' => $channel->id, 'label' => $channel->fullName];
        }, $this->transactions->channels());
    }
}
