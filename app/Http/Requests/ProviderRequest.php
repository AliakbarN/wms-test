<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::MANAGE_MASTER_DATA) ?? false;
    }

    public function rules(): array
    {
        $provider = $this->route('provider');

        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('providers', 'name')->ignore($provider instanceof Provider ? $provider->getKey() : null),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
