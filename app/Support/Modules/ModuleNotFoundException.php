<?php

declare(strict_types=1);

namespace App\Support\Modules;

use RuntimeException;

final class ModuleNotFoundException extends RuntimeException
{
    public static function forModule(string $module, string $expectedProvider): self
    {
        return new self(sprintf(
            'ماژول «%s» در config/modules.php فعال است اما ServiceProvider آن پیدا نشد. '
            .'انتظار می‌رفت کلاس %s وجود داشته باشد. '
            .'یا ماژول را با «php artisan fbh:make-module %s» بسازید، یا نامش را از فهرست enabled بردارید.',
            $module,
            $expectedProvider,
            $module,
        ));
    }
}
