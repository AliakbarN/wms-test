<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::REFUND_PURCHASE) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $idempotencyKey = $this->header('Idempotency-Key');

        $this->merge([
            'idempotency_key' => is_string($idempotencyKey) ? (trim($idempotencyKey) ?: null) : null,
        ]);
    }

    public function rules(): array
    {
        $batch = $this->route('batch');
        $batchId = $batch instanceof Batch ? $batch->getKey() : $batch;

        return [
            'refunded_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:batch_item_id,qty,unit_refund_cost'],
            'items.*.batch_item_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('batch_items', 'id')->where('batch_id', $batchId),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_refund_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
}
