<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

enum ServiceKind: string
{
    case Online = 'online';
    case Visit = 'visit';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'جلسه آنلاین',
            self::Visit => 'بازدید حضوری',
        };
    }
}
