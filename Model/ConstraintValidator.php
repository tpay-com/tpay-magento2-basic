<?php

declare(strict_types=1);

namespace Tpay\Magento2\Model;

use Magento\Checkout\Model\Session;

class ConstraintValidator
{
    /** @var Session */
    private $checkoutSession;

    public function __construct(Session $session)
    {
        $this->checkoutSession = $session;
    }

    public function validate(array $constraints, string $browser): bool
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
                case 'supported':
                    if (!$this->validateBrowser($constraint['field'], $browser)) {
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
        return $this->checkoutSession->getQuote()->getBaseGrandTotal() >= $minimal;
    }

    private function validateMaximalTotal(float $maximal): bool
    {
        return $this->checkoutSession->getQuote()->getBaseGrandTotal() <= $maximal;
    }

    private function validateBrowser(string $browserSupport, string $browser): bool
    {
        if ($browserSupport == 'ApplePaySession' && $browser != 'Safari') {
            return false;
        }

        return true;
    }
}
