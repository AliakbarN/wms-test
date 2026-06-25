<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case ProviderRefundOut = 'provider_refund_out';
    case SaleOut = 'sale_out';
    case ClientRefundIn = 'client_refund_in';
    case ManualAdjustment = 'manual_adjustment';
}
