<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * سرنوشت پیامک یک اعلان. اعلان درون‌سایتی در هر حالت سر جایش است.
 */
enum SmsDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
