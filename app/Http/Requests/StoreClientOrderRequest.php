<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Http\Requests\Concerns\DetectsIdempotencyReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientOrderRequest extends FormRequest
{
    use DetectsIdempotencyReservation;

    public function authorize(): bool
    {
        return $this->user()?->can(Ability::CREATE_CLIENT_ORDER) ?? false;
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

        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->when(
                    ! $hasReservation,
                    fn ($query) => $query->where('is_active', true),
                ),
            ],
            'ordered_at' => ['nullable', 'date'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['required', 'array:id,qty'],
            'products.*.id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('products', 'id')->when(
                    ! $hasReservation,
                    fn ($query) => $query->where('is_active', true),
                ),
            ],
            'products.*.qty' => ['required', 'integer', 'min:1'],
            'batch_id' => ['prohibited'],
            'unit_cost' => ['prohibited'],
            'unit_sale_price' => ['prohibited'],
            'sale_price' => ['prohibited'],
            'price' => ['prohibited'],
        ];
    }
}
