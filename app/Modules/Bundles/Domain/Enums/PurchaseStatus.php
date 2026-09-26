<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
