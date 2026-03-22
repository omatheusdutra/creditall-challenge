<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount' => $this->filled('discount') ? $this->input('discount') : '0',
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['bail', 'required', 'integer', Rule::exists(Product::class, 'id')],
            'customer_id' => ['bail', 'required', 'integer', Rule::exists(Customer::class, 'id')],
            'sold_at' => ['bail', 'required', 'date'],
            'quantity' => ['bail', 'required', 'integer', 'gt:0'],
            'discount' => ['bail', 'required', 'numeric', 'min:0'],
            'status' => ['bail', 'required', Rule::enum(SaleStatus::class)],
        ];
    }
}
