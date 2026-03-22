<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_creates_a_valid_sale(): void
    {
        $product = Product::factory()->create(['price' => '50.00']);
        $customer = Customer::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => '2026-03-20 10:30:00',
            'quantity' => 2,
            'discount' => '5.00',
            'status' => SaleStatus::Pending->value,
        ];

        $this->postJson('/api/v1/sales', $payload)
            ->assertCreated()
            ->assertJsonPath('data.unit_price', '50.00')
            ->assertJsonPath('data.gross_amount', '100.00')
            ->assertJsonPath('data.final_amount', '95.00')
            ->assertJsonMissingPath('data.customer.email')
            ->assertJsonMissingPath('data.customer.cpf');

        $this->assertDatabaseHas('sales', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'unit_price' => '50.00',
            'gross_amount' => '100.00',
            'final_amount' => '95.00',
        ]);
    }

    public function test_it_rejects_a_sale_with_invalid_quantity(): void
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => '2026-03-20 10:30:00',
            'quantity' => 0,
            'discount' => '0.00',
            'status' => SaleStatus::Pending->value,
        ];

        $this->postJson('/api/v1/sales', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_it_rejects_a_sale_with_invalid_discount(): void
    {
        $product = Product::factory()->create(['price' => '40.00']);
        $customer = Customer::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => '2026-03-20 10:30:00',
            'quantity' => 1,
            'discount' => '50.00',
            'status' => SaleStatus::Pending->value,
        ];

        $this->postJson('/api/v1/sales', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Sale discount cannot exceed the gross amount.');
    }

    public function test_it_lists_sales_with_pagination(): void
    {
        Sale::factory()->count(3)->create();

        $this->getJson('/api/v1/sales?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_it_updates_a_sale_and_preserves_the_historical_unit_price_when_product_is_unchanged(): void
    {
        $product = Product::factory()->create(['price' => '50.00']);
        $customer = Customer::factory()->create();
        $sale = Sale::factory()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'quantity' => 2,
            'discount' => '5.00',
            'status' => SaleStatus::Pending,
            'unit_price' => '50.00',
            'gross_amount' => '100.00',
            'final_amount' => '95.00',
        ]);

        $product->update(['price' => '80.00']);

        $payload = [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => '2026-03-21 11:30:00',
            'quantity' => 3,
            'discount' => '10.00',
            'status' => SaleStatus::Completed->value,
        ];

        $this->putJson("/api/v1/sales/{$sale->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.unit_price', '50.00')
            ->assertJsonPath('data.gross_amount', '150.00')
            ->assertJsonPath('data.final_amount', '140.00')
            ->assertJsonPath('data.status', SaleStatus::Completed->value);
    }

    public function test_it_deletes_a_sale(): void
    {
        $sale = Sale::factory()->create();

        $this->deleteJson("/api/v1/sales/{$sale->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sales', [
            'id' => $sale->id,
        ]);
    }

    public function test_it_updates_a_sale_and_refreshes_the_historical_unit_price_when_product_changes(): void
    {
        $originalProduct = Product::factory()->create(['price' => '50.00']);
        $newProduct = Product::factory()->create(['price' => '80.00']);
        $customer = Customer::factory()->create();
        $sale = Sale::factory()->create([
            'product_id' => $originalProduct->id,
            'customer_id' => $customer->id,
            'quantity' => 2,
            'discount' => '5.00',
            'status' => SaleStatus::Pending,
            'unit_price' => '50.00',
            'gross_amount' => '100.00',
            'final_amount' => '95.00',
        ]);

        $payload = [
            'product_id' => $newProduct->id,
            'customer_id' => $customer->id,
            'sold_at' => '2026-03-21 11:30:00',
            'quantity' => 3,
            'discount' => '10.00',
            'status' => SaleStatus::Completed->value,
        ];

        $this->putJson("/api/v1/sales/{$sale->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.product_id', $newProduct->id)
            ->assertJsonPath('data.unit_price', '80.00')
            ->assertJsonPath('data.gross_amount', '240.00')
            ->assertJsonPath('data.final_amount', '230.00');
    }

    public function test_it_filters_sales_by_sold_to_including_the_end_of_the_day(): void
    {
        $product = Product::factory()->create(['price' => '100.00']);
        $customer = Customer::factory()->create();

        $includedSale = Sale::factory()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => Carbon::parse('2026-03-21 23:59:59'),
            'unit_price' => '100.00',
            'gross_amount' => '100.00',
            'discount' => '0.00',
            'final_amount' => '100.00',
        ]);

        Sale::factory()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'sold_at' => Carbon::parse('2026-03-22 00:00:00'),
            'unit_price' => '100.00',
            'gross_amount' => '100.00',
            'discount' => '0.00',
            'final_amount' => '100.00',
        ]);

        $this->getJson('/api/v1/sales?sold_to=2026-03-21')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $includedSale->id);
    }

    public function test_it_returns_not_found_for_an_unknown_sale(): void
    {
        $this->getJson('/api/v1/sales/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource was not found.');
    }
}
