<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Http\Requests\Concerns\DetectsIdempotencyReservation;
use App\Models\Batch;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseRequest extends FormRequest
{
    use DetectsIdempotencyReservation;

    public function authorize(): bool
    {
        return $this->user()?->can(Ability::CREATE_PURCHASE) ?? false;
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
        $hasReservation = $this->hasReservedIdempotencyKey();
        $idempotencyKey = $this->input('idempotency_key');
        $idempotentBatchId = $idempotencyKey === null
            ? null
            : Batch::query()->where('idempotency_key', $idempotencyKey)->value('id');

        return [
            'provider_id' => [
                'required',
                'integer',
                Rule::exists('providers', 'id')->when(
                    ! $hasReservation,
                    fn ($query) => $query->where('is_active', true),
                ),
            ],
            'batch_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('batches', 'batch_no')->ignore($idempotentBatchId),
            ],
            'purchased_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:product_id,storage_id,qty,unit_cost'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->when(
                    ! $hasReservation,
                    fn ($query) => $query->where('is_active', true),
                ),
            ],
            'items.*.storage_id' => [
                'required',
                'integer',
                Rule::exists('storages', 'id')->when(
                    ! $hasReservation,
                    fn ($query) => $query->where('is_active', true),
                ),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $providerId = $this->integer('provider_id');

                foreach ($this->input('items', []) as $index => $item) {
                    if (! is_array($item) || ! isset($item['product_id'])) {
                        continue;
                    }

                    $product = Product::query()
                        ->with('category:id,provider_id')
                        ->find($item['product_id']);

                    if ($product !== null && $product->category?->provider_id !== $providerId) {
                        $validator->errors()->add(
                            "items.{$index}.product_id",
                            'The product does not belong to the selected provider.',
                        );
                    }
                }
            },
        ];
    }
}
