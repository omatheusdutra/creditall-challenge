<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_creates_a_customer(): void
    {
        $payload = [
            'name' => 'Ana Martins',
            'email' => 'ana.martins@example.com',
            'cpf' => '529.982.247-25',
        ];

        $this->postJson('/api/v1/customers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.email', $payload['email'])
            ->assertJsonPath('data.cpf', '***.***.***-25');

        $this->assertDatabaseHas('customers', [
            'email' => $payload['email'],
            'cpf' => '52998224725',
        ]);
    }

    public function test_it_rejects_duplicate_customer_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $payload = Customer::factory()->make([
            'email' => $existingCustomer->email,
        ])->toArray();

        $this->postJson('/api/v1/customers', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_rejects_duplicate_customer_cpf(): void
    {
        $existingCustomer = Customer::factory()->create([
            'cpf' => '52998224725',
        ]);

        $payload = Customer::factory()->make([
            'cpf' => $existingCustomer->cpf,
        ])->toArray();

        $this->postJson('/api/v1/customers', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_it_rejects_invalid_customer_cpf(): void
    {
        $payload = Customer::factory()->make([
            'cpf' => '12345678900',
        ])->toArray();

        $this->postJson('/api/v1/customers', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_it_lists_customers_with_pagination(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_it_filters_customers_by_cpf_prefix(): void
    {
        $matchingCustomer = Customer::factory()->create([
            'cpf' => '52998224725',
        ]);

        Customer::factory()->create([
            'cpf' => '11144477735',
        ]);

        $this->getJson('/api/v1/customers?search=529982')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingCustomer->id);
    }

    public function test_it_updates_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'name' => 'Marina Oliveira',
            'email' => 'marina.oliveira@example.com',
            'cpf' => '11144477735',
        ];

        $this->putJson("/api/v1/customers/{$customer->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.cpf', '***.***.***-35');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => $payload['email'],
            'cpf' => $payload['cpf'],
        ]);
    }

    public function test_it_deletes_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_it_masks_cpf_in_customer_responses(): void
    {
        $customer = Customer::factory()->create([
            'cpf' => '52998224725',
        ]);

        $this->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.cpf', '***.***.***-25');
    }

    public function test_it_cannot_delete_a_customer_that_is_linked_to_sales(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => '25.00']);
        Sale::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'unit_price' => '25.00',
            'gross_amount' => '25.00',
            'discount' => '0.00',
            'final_amount' => '25.00',
        ]);

        $this->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'This customer cannot be deleted because it is referenced by existing sales.');
    }

    public function test_it_returns_not_found_for_an_unknown_customer(): void
    {
        $this->getJson('/api/v1/customers/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource was not found.');
    }
}
