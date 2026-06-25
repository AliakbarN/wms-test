<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BatchProfitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::VIEW_REPORTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['from', 'to']) && $this->date('to')->lt($this->date('from'))) {
                    $validator->errors()->add('to', 'The to date must be on or after the from date.');
                }
            },
        ];
    }
}
