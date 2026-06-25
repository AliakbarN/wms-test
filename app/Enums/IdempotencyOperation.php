<?php

namespace App\Enums;

enum IdempotencyOperation: string
{
    case Purchase = 'purchase.create';
    case ProviderRefund = 'provider_refund.create';
    case ClientOrder = 'client_order.create';
    case ClientRefund = 'client_refund.create';
}
