<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = number_format(fake()->randomFloat(2, 10, 5000), 2, '.', '');
        $grossAmount = number_format((float) $unitPrice * $quantity, 2, '.', '');
        $discount = number_format(fake()->randomFloat(2, 0, (float) $grossAmount), 2, '.', '');

        return [
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'sold_at' => fake()->dateTimeBetween('-30 days'),
            'quantity' => $quantity,
            'discount' => $discount,
            'status' => fake()->randomElement(SaleStatus::cases()),
            'unit_price' => $unitPrice,
            'gross_amount' => $grossAmount,
            'final_amount' => number_format(max((float) $grossAmount - (float) $discount, 0), 2, '.', ''),
        ];
    }
}
