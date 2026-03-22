<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => trim((string) $this->input('description')),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:150'],
            'description' => ['bail', 'required', 'string', 'max:5000'],
            'price' => ['bail', 'required', 'numeric', 'gt:0'],
            'image' => ['bail', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['bail', 'nullable', 'boolean'],
        ];
    }
}
