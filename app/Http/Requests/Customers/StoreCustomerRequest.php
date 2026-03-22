<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Rules\ValidCpf;
use App\Support\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'cpf' => Cpf::sanitize($this->input('cpf')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:150'],
            'email' => ['bail', 'required', 'email:rfc', 'max:150', Rule::unique(Customer::class, 'email')],
            'cpf' => ['bail', 'required', 'string', 'size:11', new ValidCpf, Rule::unique(Customer::class, 'cpf')],
        ];
    }
}
