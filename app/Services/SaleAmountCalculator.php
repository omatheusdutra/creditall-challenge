<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\SaleAmountsData;
use App\Exceptions\BusinessRuleViolationException;

class SaleAmountCalculator
{
    public function calculate(string $unitPrice, int $quantity, string $discount): SaleAmountsData
    {
        if ($quantity <= 0) {
            throw new BusinessRuleViolationException('Sale quantity must be greater than zero.');
        }

        $unitPriceInCents = $this->toCents($unitPrice);
        $discountInCents = $this->toCents($discount);
        $grossAmountInCents = $unitPriceInCents * $quantity;

        if ($discountInCents < 0) {
            throw new BusinessRuleViolationException('Sale discount cannot be negative.');
        }

        if ($discountInCents > $grossAmountInCents) {
            throw new BusinessRuleViolationException('Sale discount cannot exceed the gross amount.');
        }

        return new SaleAmountsData(
            unitPrice: $this->fromCents($unitPriceInCents),
            grossAmount: $this->fromCents($grossAmountInCents),
            discount: $this->fromCents($discountInCents),
            finalAmount: $this->fromCents($grossAmountInCents - $discountInCents),
        );
    }

    private function toCents(string $amount): int
    {
        $normalizedAmount = str_replace(',', '.', trim($amount));

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalizedAmount)) {
            throw new BusinessRuleViolationException('The informed monetary amount is invalid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalizedAmount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2, '.', '');
    }
}
