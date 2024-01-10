<?php

declare(strict_types=1);

namespace tpaycom\magento2basic\Model\ApiFacade\Transaction\Dto;

class Channel
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $fullName;

    /** @var string */
    public $image;

    /** @var bool */
    public $available;

    /** @var bool */
    public $onlinePayment;

    /** @var bool */
    public $instantRedirection;

    /** @var array */
    public $groups;

    /** @var array */
    public $constraints;

    public function __construct(
        int $id,
        string $name,
        string $fullName,
        string $image,
        bool $available,
        bool $onlinePayment,
        bool $instantRedirection,
        array $groups,
        array $constraints
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->fullName = $fullName;
        $this->image = $image;
        $this->available = $available;
        $this->onlinePayment = $onlinePayment;
        $this->instantRedirection = $instantRedirection;
        $this->groups = $groups;
        $this->constraints = $constraints;
    }
}
