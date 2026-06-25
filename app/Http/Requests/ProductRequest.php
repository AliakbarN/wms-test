<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::MANAGE_MASTER_DATA) ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'integer',
                'exists:categories,id',
            ],
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product instanceof Product ? $product->getKey() : null),
            ],
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'default_sale_price' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
