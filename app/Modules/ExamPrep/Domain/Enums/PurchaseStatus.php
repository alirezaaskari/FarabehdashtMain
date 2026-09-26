<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
}
