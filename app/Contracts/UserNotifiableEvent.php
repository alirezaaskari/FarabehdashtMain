<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Notifications\UserNotice;

/**
 * رویدادی که کسی باید از آن باخبر شود.
 *
 * همان الگوی {@see AuditableEvent}: ماژول منتشرکننده فقط می‌گوید «به چه کسی،
 * چه چیزی»؛ رساندنش کار ماژول میزکار است و منتشرکننده از آن خبر ندارد. اگر
 * میزکار غیرفعال باشد، رویداد منتشر می‌شود و کسی گوش نمی‌دهد — بدون خطا.
 */
interface UserNotifiableEvent
{
    /** @return list<UserNotice> */
    public function userNotices(): array;
}
