<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SaleService
{
    private const SUMMARY_RELATIONS = [
        'product:id,name',
        'customer:id,name',
    ];

    public function __construct(
        private readonly SaleAmountCalculator $saleAmountCalculator,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sort = (string) ($filters['sort'] ?? 'sold_at');
        $sort = in_array($sort, ['sold_at', 'created_at', 'final_amount', 'status'], true) ? $sort : 'sold_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return Sale::query()
            ->select([
                'id',
                'product_id',
                'customer_id',
                'sold_at',
                'quantity',
                'discount',
                'status',
                'unit_price',
                'gross_amount',
                'final_amount',
                'created_at',
                'updated_at',
            ])
            ->with(self::SUMMARY_RELATIONS)
            ->when(
                $filters['status'] ?? null,
                static fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->when(
                $filters['product_id'] ?? null,
                static fn (Builder $query, int $productId): Builder => $query->where('product_id', $productId),
            )
            ->when(
                $filters['customer_id'] ?? null,
                static fn (Builder $query, int $customerId): Builder => $query->where('customer_id', $customerId),
            )
            ->when(
                $filters['sold_from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->where('sold_at', '>=', $this->toStartOfDay($date)),
            )
            ->when(
                $filters['sold_to'] ?? null,
                fn (Builder $query, string $date): Builder => $query->where('sold_at', '<=', $this->toEndOfDay($date)),
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $product = $this->findProduct((int) $data['product_id']);
            $this->ensureCustomerExists((int) $data['customer_id']);

            $sale = Sale::query()->create(
                $this->buildPayload(
                    product: $product,
                    currentSale: null,
                    data: $data,
                ),
            );

            return $this->loadSummaryRelations($sale);
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data): Sale {
            $product = (int) $data['product_id'] === $sale->product_id
                ? $sale->product()->firstOrFail()
                : $this->findProduct((int) $data['product_id']);

            $this->ensureCustomerExists((int) $data['customer_id']);

            $sale->fill($this->buildPayload(
                product: $product,
                currentSale: $sale,
                data: $data,
            ));

            $sale->save();

            return $this->loadSummaryRelations($sale);
        });
    }

    public function delete(Sale $sale): void
    {
        $sale->delete();
    }

    private function buildPayload(Product $product, ?Sale $currentSale, array $data): array
    {
        $productChanged = $currentSale === null || $product->id !== $currentSale->product_id;
        $unitPrice = $productChanged ? (string) $product->price : (string) $currentSale->unit_price;
        $amounts = $this->saleAmountCalculator->calculate(
            unitPrice: $unitPrice,
            quantity: (int) $data['quantity'],
            discount: (string) ($data['discount'] ?? '0'),
        );

        return [
            'product_id' => $product->id,
            'customer_id' => (int) $data['customer_id'],
            'sold_at' => $this->toSoldAt($data['sold_at']),
            'quantity' => (int) $data['quantity'],
            'discount' => $amounts->discount,
            'status' => $data['status'],
            'unit_price' => $amounts->unitPrice,
            'gross_amount' => $amounts->grossAmount,
            'final_amount' => $amounts->finalAmount,
        ];
    }

    private function ensureCustomerExists(int $customerId): void
    {
        Customer::query()->findOrFail($customerId);
    }

    private function findProduct(int $productId): Product
    {
        return Product::query()->findOrFail($productId);
    }

    private function loadSummaryRelations(Sale $sale): Sale
    {
        return $sale->load(self::SUMMARY_RELATIONS);
    }

    private function toStartOfDay(string $date): string
    {
        return CarbonImmutable::parse($date)->startOfDay()->toDateTimeString();
    }

    private function toEndOfDay(string $date): string
    {
        return CarbonImmutable::parse($date)->endOfDay()->toDateTimeString();
    }

    private function toSoldAt(string $date): string
    {
        return CarbonImmutable::parse($date)->format('Y-m-d H:i:s');
    }
}
