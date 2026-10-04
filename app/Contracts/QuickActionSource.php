<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\Support\Search\QuickAction;

/**
 * ماژولی که کاری در پنل فرمان (Ctrl+K) پیشنهاد می‌دهد.
 *
 * پنل فرمان فقط جست‌وجو نیست؛ «گزارش تازه» یا «پرسش از متخصص» را هم بی‌گشتن
 * در منو اجرا می‌کند. هر ماژول کارهای خودش را می‌دهد و فقط آن‌هایی که این
 * کاربر اجازه‌شان را دارد. مهمان با null می‌آید.
 *
 * برچسب روی قرارداد است، همان الگوی {@see SearchSource}، تا حذف ماژول میزکار
 * ماژول‌های ثبت‌کننده را نشکند (قاعده ۲).
 */
interface QuickActionSource
{
    public const TAG = 'quick-actions.sources';

    /** @return list<QuickAction> */
    public function quickActions(?User $user): array;
}
