<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Domain\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
