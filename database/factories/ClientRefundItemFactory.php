<?php

namespace Database\Factories;

use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\ClientRefund;
use App\Models\ClientRefundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClientRefundItem> */
class ClientRefundItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_refund_id' => ClientRefund::factory(),
            'order_allocation_id' => function (array $attributes): int {
                $refund = ClientRefund::query()->findOrFail($attributes['client_refund_id']);
                $orderItem = ClientOrderItem::factory()->for($refund->order, 'order')->create();

                return ClientOrderAllocation::factory()->for($orderItem, 'orderItem')->create()->getKey();
            },
            'product_id' => fn (array $attributes): int => $this->allocation($attributes)->product_id,
            'batch_item_id' => fn (array $attributes): int => $this->allocation($attributes)->batch_item_id,
            'storage_id' => fn (array $attributes): int => $this->allocation($attributes)->storage_id,
            'qty' => 1,
            'unit_sale_price' => fn (array $attributes): string => $this->allocation($attributes)->unit_sale_price,
            'unit_cost' => fn (array $attributes): string => $this->allocation($attributes)->unit_cost,
            'restock' => true,
        ];
    }

    private function allocation(array $attributes): ClientOrderAllocation
    {
        return ClientOrderAllocation::query()->findOrFail($attributes['order_allocation_id']);
    }
}
