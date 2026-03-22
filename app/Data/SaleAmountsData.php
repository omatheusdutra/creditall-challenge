<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SaleAmountsData
{
    public function __construct(
        public string $unitPrice,
        public string $grossAmount,
        public string $discount,
        public string $finalAmount,
    ) {}
}
