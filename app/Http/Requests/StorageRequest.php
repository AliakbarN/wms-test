<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\Storage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::MANAGE_MASTER_DATA) ?? false;
    }

    public function rules(): array
    {
        $storage = $this->route('storage');

        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('storages', 'name')->ignore($storage instanceof Storage ? $storage->getKey() : null),
            ],
            'address' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
