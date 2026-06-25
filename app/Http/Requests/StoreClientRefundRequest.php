<?php

namespace App\Http\Requests;

use App\Enums\Ability;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClientRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Ability::REFUND_CLIENT_ORDER) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        $idempotencyKey = $this->header('Idempotency-Key');

        if (! is_array($items)) {
            $this->merge([
                'idempotency_key' => is_string($idempotencyKey) ? (trim($idempotencyKey) ?: null) : null,
            ]);

            return;
        }

        foreach ($items as &$item) {
            if (is_array($item) && ! array_key_exists('restock', $item)) {
                $item['restock'] = true;
            }
        }
        unset($item);

        $this->merge([
            'items' => $items,
            'idempotency_key' => is_string($idempotencyKey) ? (trim($idempotencyKey) ?: null) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'refunded_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:order_allocation_id,qty,restock'],
            'items.*.order_allocation_id' => [
                'required',
                'integer',
                'distinct:strict',
                'exists:client_order_allocations,id',
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.restock' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $order = $this->route('order');

                foreach ($this->input('items', []) as $index => $item) {
                    if (! is_array($item) || ! isset($item['order_allocation_id'], $item['qty'])) {
                        continue;
                    }

                    $allocation = ClientOrderAllocation::query()
                        ->with('orderItem:id,order_id')
                        ->find($item['order_allocation_id']);

                    if ($allocation === null || ! $order instanceof ClientOrder) {
                        continue;
                    }

                    if ($allocation->orderItem->order_id !== $order->getKey()) {
                        $validator->errors()->add(
                            "items.{$index}.order_allocation_id",
                            'The allocation does not belong to this order.',
                        );

                        continue;
                    }
                }
            },
        ];
    }
}
