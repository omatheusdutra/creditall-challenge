<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleViolationException;
use App\Models\Customer;
use App\Support\Cpf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $sort = in_array($sort, ['name', 'email', 'created_at'], true) ? $sort : 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $this->normalizeSearch($filters['search'] ?? null);

        return Customer::query()
            ->select(['id', 'name', 'email', 'cpf', 'created_at', 'updated_at'])
            ->when(
                $search,
                fn (Builder $query, string $search): Builder => $this->applySearch($query, $search),
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Customer
    {
        return Customer::query()->create($this->normalizePayload($data))->refresh();
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->fill($this->normalizePayload($data));
        $customer->save();

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        if ($customer->sales()->exists()) {
            throw new BusinessRuleViolationException(
                'This customer cannot be deleted because it is referenced by existing sales.',
                409,
            );
        }

        $customer->delete();
    }

    private function normalizePayload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'email' => Str::lower(trim((string) $data['email'])),
            'cpf' => Cpf::sanitize((string) $data['cpf']),
        ];
    }

    private function normalizeSearch(?string $search): ?string
    {
        $normalizedSearch = trim((string) $search);

        return $normalizedSearch === '' ? null : $normalizedSearch;
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $sanitizedCpf = Cpf::sanitize($search);

        if ($sanitizedCpf !== '') {
            return $query->where('cpf', 'like', "{$sanitizedCpf}%");
        }

        return $query->where(function (Builder $nestedQuery) use ($search): void {
            if ($this->usesMysql() && mb_strlen($search) >= 3) {
                $nestedQuery->whereRaw(
                    'MATCH(name, email) AGAINST (? IN BOOLEAN MODE)',
                    [$this->toBooleanModeSearch($search)],
                )->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                return;
            }

            $nestedQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
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
