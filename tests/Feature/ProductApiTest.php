<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_creates_a_product(): void
    {
        $payload = [
            'name' => 'Premium Keyboard',
            'description' => 'Mechanical keyboard with hot-swappable switches.',
            'price' => '499.90',
        ];

        $this->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.price', $payload['price']);

        $this->assertDatabaseHas('products', [
            'name' => $payload['name'],
            'price' => $payload['price'],
        ]);
    }

    public function test_it_rejects_a_product_with_invalid_price(): void
    {
        $payload = [
            'name' => 'Broken Product',
            'description' => 'Should not be persisted.',
            'price' => '0.00',
        ];

        $this->postJson('/api/v1/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_it_lists_products_with_pagination(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_it_filters_products_by_search_term(): void
    {
        $matchingProduct = Product::factory()->create([
            'name' => 'Mechanical Keyboard',
            'description' => 'Hot-swappable 75 percent keyboard.',
        ]);

        Product::factory()->create([
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic office mouse.',
        ]);

        $this->getJson('/api/v1/products?search=Keyboard')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id);
    }

    public function test_it_updates_a_product(): void
    {
        $product = Product::factory()->create();

        $payload = [
            'name' => 'Updated Monitor',
            'description' => '4K IPS panel.',
            'price' => '1599.90',
            'remove_image' => false,
        ];

        $this->putJson("/api/v1/products/{$product->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.price', $payload['price']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $payload['name'],
            'price' => $payload['price'],
        ]);
    }

    public function test_it_deletes_a_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_it_uploads_a_product_image(): void
    {
        Storage::fake('public');

        $payload = [
            'name' => 'Product With Image',
            'description' => 'Includes an uploaded image.',
            'price' => '79.90',
            'image' => UploadedFile::fake()->image('product.jpg', 300, 300),
        ];

        $response = $this->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', $payload['name']);

        $imagePath = $response->json('data.image.path');

        $this->assertNotNull($imagePath);
        Storage::disk('public')->assertExists($imagePath);
        $this->assertDatabaseHas('products', [
            'name' => $payload['name'],
            'image_path' => $imagePath,
        ]);
    }

    public function test_it_rejects_an_invalid_product_image_upload(): void
    {
        Storage::fake('public');

        $payload = [
            'name' => 'Invalid Image Product',
            'description' => 'Should reject an invalid file.',
            'price' => '149.90',
            'image' => UploadedFile::fake()->create('malicious.pdf', 50, 'application/pdf'),
        ];

        $this->postJson('/api/v1/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_it_replaces_an_existing_product_image_safely(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'image_path' => 'products/original.jpg',
        ]);

        Storage::disk('public')->put($product->image_path, 'legacy-image');

        $payload = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => '89.90',
            'remove_image' => false,
            'image' => UploadedFile::fake()->image('replacement.jpg', 300, 300),
        ];

        $response = $this->putJson("/api/v1/products/{$product->id}", $payload)
            ->assertOk();

        $newImagePath = $response->json('data.image.path');

        $this->assertNotSame('products/original.jpg', $newImagePath);
        Storage::disk('public')->assertMissing('products/original.jpg');
        Storage::disk('public')->assertExists($newImagePath);
    }

    public function test_it_cannot_delete_a_product_that_is_linked_to_sales(): void
    {
        $product = Product::factory()->create(['price' => '25.00']);
        $customer = Customer::factory()->create();
        Sale::factory()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'unit_price' => '25.00',
            'gross_amount' => '25.00',
            'discount' => '0.00',
            'final_amount' => '25.00',
        ]);

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'This product cannot be deleted because it is referenced by existing sales.');
    }

    public function test_it_returns_not_found_for_an_unknown_product(): void
    {
        $this->getJson('/api/v1/products/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource was not found.');
    }
}
