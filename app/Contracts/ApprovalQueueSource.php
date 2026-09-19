<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Admin\PendingItem;

/**
 * ماژولی که موردی منتظر تصمیم مدیر دارد.
 *
 * هر ماژول محتوایی یا مالی این را پیاده می‌کند و با برچسب
 * `AdminServiceProvider::APPROVAL_SOURCES` در کانتینر ثبت می‌شود؛ ماژول Admin
 * صف یکپارچه را می‌سازد بدون اینکه بداند چه ماژول‌هایی وجود دارند.
 */
interface ApprovalQueueSource
{
    /**
     * @return iterable<PendingItem>
     */
    public function pendingItems(): iterable;
}
