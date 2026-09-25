<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    case MobileChange = 'mobile_change';
}
