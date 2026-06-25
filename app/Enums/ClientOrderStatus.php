<?php

namespace App\Enums;

enum ClientOrderStatus: string
{
    case Confirmed = 'confirmed';
    case PartiallyRefunded = 'partially_refunded';
    case FullyRefunded = 'fully_refunded';
    case Cancelled = 'cancelled';
}
