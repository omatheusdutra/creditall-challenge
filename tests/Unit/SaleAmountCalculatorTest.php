<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\BusinessRuleViolationException;
use App\Services\SaleAmountCalculator;
use PHPUnit\Framework\TestCase;

class SaleAmountCalculatorTest extends TestCase
{
    public function test_it_calculates_sale_amounts(): void
    {
        $calculator = new SaleAmountCalculator;

        $amounts = $calculator->calculate('99.90', 3, '19.80');

        $this->assertSame('99.90', $amounts->unitPrice);
        $this->assertSame('299.70', $amounts->grossAmount);
        $this->assertSame('19.80', $amounts->discount);
        $this->assertSame('279.90', $amounts->finalAmount);
    }

    public function test_it_rejects_discount_greater_than_gross_amount(): void
    {
        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('Sale discount cannot exceed the gross amount.');

        (new SaleAmountCalculator)->calculate('10.00', 1, '11.00');
    }
}
