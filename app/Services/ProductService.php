<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleViolationException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductService
{
    public function __construct(
        private readonly ProductImageService $productImageService,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $sort = in_array($sort, ['name', 'price', 'created_at'], true) ? $sort : 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $this->normalizeSearch($filters['search'] ?? null);

        return Product::query()
            ->select(['id', 'name', 'description', 'price', 'image_path', 'created_at', 'updated_at'])
            ->when(
                $search,
                fn (Builder $query, string $search): Builder => $this->applySearch($query, $search),
            )
            ->when(
                $filters['min_price'] ?? null,
                static fn (Builder $query, string $minPrice): Builder => $query->where('price', '>=', $minPrice),
            )
            ->when(
                $filters['max_price'] ?? null,
                static fn (Builder $query, string $maxPrice): Builder => $query->where('price', '<=', $maxPrice),
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?UploadedFile $image = null): Product
    {
        $storedImage = null;
        $payload = $this->normalizePayload($data);

        try {
            if ($image !== null) {
                $storedImage = $this->productImageService->store($image);
                $payload['image_path'] = $storedImage;
            }

            return DB::transaction(static fn (): Product => Product::query()->create($payload)->refresh());
        } catch (Throwable $exception) {
            if ($storedImage !== null) {
                $this->productImageService->delete($storedImage);
            }

            throw $exception;
        }
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        $storedImage = null;
        $previousImage = $product->image_path;
        $removeImage = (bool) ($data['remove_image'] ?? false);
        $payload = $this->normalizePayload($data);

        unset($payload['remove_image']);

        try {
            if ($image !== null) {
                $storedImage = $this->productImageService->store($image);
                $payload['image_path'] = $storedImage;
            } elseif ($removeImage) {
                $payload['image_path'] = null;
            }

            DB::transaction(function () use ($product, $payload): void {
                $product->fill($payload);
                $product->save();
            });
        } catch (Throwable $exception) {
            if ($storedImage !== null) {
                $this->productImageService->delete($storedImage);
            }

            throw $exception;
        }

        if (($storedImage !== null || $removeImage) && $previousImage !== null) {
            $this->productImageService->delete($previousImage);
        }

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        if ($product->sales()->exists()) {
            throw new BusinessRuleViolationException(
                'This product cannot be deleted because it is referenced by existing sales.',
                409,
            );
        }

        $imagePath = $product->image_path;
        $product->delete();
        $this->productImageService->delete($imagePath);
    }

    private function normalizePayload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'description' => trim((string) $data['description']),
            'price' => (string) $data['price'],
        ];
    }

    private function normalizeSearch(?string $search): ?string
    {
        $normalizedSearch = trim((string) $search);

        return $normalizedSearch === '' ? null : $normalizedSearch;
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $nestedQuery) use ($search): void {
            if ($this->usesMysql() && mb_strlen($search) >= 3) {
                $nestedQuery->whereRaw(
                    'MATCH(name, description) AGAINST (? IN BOOLEAN MODE)',
                    [$this->toBooleanModeSearch($search)],
                )->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");

                return;
            }

            $nestedQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    private function toBooleanModeSearch(string $search): string
    {
        $terms = array_filter(
            preg_split('/\s+/', trim($search)) ?: [],
            static fn (string $term): bool => $term !== '',
        );

        if ($terms === []) {
            return $search;
        }

        return implode(' ', array_map(
            static fn (string $term): string => sprintf('%s*', $term),
            $terms,
        ));
    }

    private function usesMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }
}
