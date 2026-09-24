<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Listeners;

use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Workspace\Actions\AcceptLegalVersions;

/**
 * فرم ورود کادر «قوانین و حریم خصوصی را می‌پذیرم» دارد و بدون آن کد
 * فرستاده نمی‌شود. همان تیک، پذیرش نسخه‌های جاری است و این‌جا ثبت می‌شود؛
 * وگرنه کاربر تازه درست بعد از ورود دوباره به صفحه پذیرش فرستاده می‌شد.
 *
 * صفحه پذیرش دوباره برای کسی است که نشستش باز مانده و نسخه اساسی تازه در
 * این فاصله منتشر شده.
 */
final readonly class AcceptLegalOnSignIn
{
    public function __construct(private AcceptLegalVersions $accept) {}

    public function handle(UserSignedIn $event): void
    {
        $this->accept->handle((int) $event->user->getKey());
    }
}
