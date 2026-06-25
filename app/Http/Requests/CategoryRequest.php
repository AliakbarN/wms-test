<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::MANAGE_MASTER_DATA) ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $providerId = $this->input('provider_id', $category instanceof Category ? $category->provider_id : null);
        $parentId = $this->exists('parent_id')
            ? $this->input('parent_id')
            : ($category instanceof Category ? $category->parent_id : null);

        return [
            'provider_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'integer',
                'exists:providers,id',
            ],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query
                        ->where('provider_id', $providerId)
                        ->when(
                            $parentId === null,
                            fn ($query) => $query->whereNull('parent_id'),
                            fn ($query) => $query->where('parent_id', $parentId),
                        ))
                    ->ignore($category instanceof Category ? $category->getKey() : null),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
