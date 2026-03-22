<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['bail', 'nullable', Rule::in(SaleStatus::values())],
            'product_id' => ['bail', 'nullable', 'integer', Rule::exists(Product::class, 'id')],
            'customer_id' => ['bail', 'nullable', 'integer', Rule::exists(Customer::class, 'id')],
            'sold_from' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'sold_to' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:sold_from'],
            'sort' => ['bail', 'nullable', Rule::in(['sold_at', 'created_at', 'final_amount', 'status'])],
            'direction' => ['bail', 'nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['bail', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
