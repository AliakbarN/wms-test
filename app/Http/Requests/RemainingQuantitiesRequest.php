<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use Illuminate\Foundation\Http\FormRequest;

class RemainingQuantitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::VIEW_REPORTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'storage_id' => ['nullable', 'integer', 'exists:storages,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
