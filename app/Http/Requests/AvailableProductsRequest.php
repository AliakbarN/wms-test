<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use Illuminate\Foundation\Http\FormRequest;

class AvailableProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::VIEW_INVENTORY) ?? false;
    }

    public function rules(): array
    {
        return [
            'storage_id' => ['nullable', 'integer', 'exists:storages,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
