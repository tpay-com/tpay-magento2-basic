<?php

namespace Tpay\Magento2\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HashTypes implements OptionSourceInterface
{
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value,
            ];
        }

        return $ret;
    }

    /**
     * Get options in "key-value" format
     *
     * @return list<string>
     */
    public function toArray()
    {
        return [
            'sha1' => 'sha1',
            'sha256' => 'sha256',
            'sha512' => 'sha512',
            'ripemd160' => 'ripemd160',
            'ripemd320' => 'ripemd320',
            'md5' => 'md5',
        ];
    }
}
