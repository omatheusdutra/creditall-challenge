<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        if (Sale::query()->exists()) {
            return;
        }

        $products = Product::query()->get();
        $customers = Customer::query()->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $statuses = [
            SaleStatus::Completed,
            SaleStatus::Completed,
            SaleStatus::Completed,
            SaleStatus::Pending,
            SaleStatus::Cancelled,
        ];

        foreach (range(1, 15) as $index) {
            $product = $products->random();
            $customer = $customers->random();
            $quantity = fake()->numberBetween(1, 4);
            $unitPrice = number_format((float) $product->price, 2, '.', '');
            $grossAmount = number_format((float) $unitPrice * $quantity, 2, '.', '');
            $discountCap = min((float) $grossAmount * 0.18, 750);
            $discount = number_format(fake()->randomFloat(2, 0, $discountCap), 2, '.', '');

            Sale::query()->create([
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'sold_at' => fake()->dateTimeBetween('-45 days', 'now'),
                'quantity' => $quantity,
                'discount' => $discount,
                'status' => fake()->randomElement($statuses),
                'unit_price' => $unitPrice,
                'gross_amount' => $grossAmount,
                'final_amount' => number_format(max((float) $grossAmount - (float) $discount, 0), 2, '.', ''),
            ]);
        }
    }
}
