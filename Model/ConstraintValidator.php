<?php

declare(strict_types=1);

namespace TpayCom\Magento2Basic\Model;

use Magento\Checkout\Model\Session;

class ConstraintValidator
{
    /** @var Session */
    private $checkoutSession;

    public function __construct(Session $session)
    {
        $this->checkoutSession = $session;
    }

    public function validate(array $constraints): bool
    {
        foreach ($constraints as $constraint) {
            switch ($constraint['type']) {
                case 'min':
                    if (!$this->validateMinimalTotal((float) $constraint['value'])) {
                        return false;
                    }

                    break;
                case 'max':
                    if (!$this->validateMaximalTotal((float) $constraint['value'])) {
                        return false;
                    }

                    break;
                default:
                    break;
            }
        }

        return true;
    }

    public function isClientCountryValid(bool $isAllowed, string $clientCountry, array $specificCountry): bool
    {
        return $isAllowed && !in_array($clientCountry, $specificCountry);
    }

    private function validateMinimalTotal(float $minimal): bool
    {
        return $this->checkoutSession->getQuote()->getGrandTotal() > $minimal;
    }

    private function validateMaximalTotal(float $maximal): bool
    {
        return $this->checkoutSession->getQuote()->getGrandTotal() < $maximal;
    }
}
