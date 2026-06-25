<?php

namespace App\Http\Resources;

use App\Enums\BatchStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchProfitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'batch_id' => $this->batch_id,
            'batch_no' => $this->batch_no,
            'provider_id' => $this->provider_id,
            'provider_name' => $this->provider_name,
            'purchased_at' => CarbonImmutable::parse($this->purchased_at)->toISOString(),
            'status' => $this->status instanceof BatchStatus ? $this->status->value : $this->status,
            'purchased_qty' => (int) $this->purchased_qty,
            'purchase_cost_total' => $this->money($this->purchase_cost_total),
            'provider_refunded_qty' => (int) $this->provider_refunded_qty,
            'provider_refund_value' => $this->money($this->provider_refund_value),
            'gross_sold_qty' => (int) $this->gross_sold_qty,
            'client_refunded_qty' => (int) $this->client_refunded_qty,
            'net_sold_qty' => (int) $this->net_sold_qty,
            'gross_sales_revenue' => $this->money($this->gross_sales_revenue),
            'client_refund_value' => $this->money($this->client_refund_value),
            'net_sales_revenue' => $this->money($this->net_sales_revenue),
            'cogs' => $this->money($this->cogs),
            'realized_profit' => $this->money($this->realized_profit),
            'remaining_qty' => (int) $this->remaining_qty,
            'remaining_inventory_value' => $this->money($this->remaining_inventory_value),
            'batch_financial_position' => $this->money($this->batch_financial_position),
        ];
    }

    private function money(mixed $value): string
    {
        $value = (string) ($value ?? '0');
        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }
}
