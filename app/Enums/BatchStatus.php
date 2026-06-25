<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Confirmed = 'confirmed';
    case PartiallyRefunded = 'partially_refunded';
    case FullyRefunded = 'fully_refunded';
    case Cancelled = 'cancelled';
}
