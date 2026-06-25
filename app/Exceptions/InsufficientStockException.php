<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $requestedQty,
        public readonly int $availableQty,
    ) {
        parent::__construct(
            "Requested quantity is {$requestedQty}, but only {$availableQty} units are available.",
        );
    }
}
