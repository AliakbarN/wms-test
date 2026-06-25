<?php

namespace App\Enums;

final class Ability
{
    public const VIEW_INVENTORY = 'viewInventory';

    public const CREATE_PURCHASE = 'createPurchase';

    public const REFUND_PURCHASE = 'refundPurchase';

    public const CREATE_CLIENT_ORDER = 'createClientOrder';

    public const REFUND_CLIENT_ORDER = 'refundClientOrder';

    public const VIEW_REPORTS = 'viewReports';

    public const MANAGE_MASTER_DATA = 'manageMasterData';

    public static function all(): array
    {
        return [
            self::VIEW_INVENTORY,
            self::CREATE_PURCHASE,
            self::REFUND_PURCHASE,
            self::CREATE_CLIENT_ORDER,
            self::REFUND_CLIENT_ORDER,
            self::VIEW_REPORTS,
            self::MANAGE_MASTER_DATA,
        ];
    }
}
