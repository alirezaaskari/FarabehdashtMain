<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;

/**
 * ماژولی که کارتی روی میزکار کاربر دارد.
 *
 * میزکار نمی‌داند چه ماژول‌هایی هستند؛ هر ماژول این قرارداد را پیاده و با
 * برچسب {@see self::TAG} ثبت می‌کند — همان الگوی `HomepageSource`.
 *
 * برچسب روی خود قرارداد است، نه روی ServiceProvider ماژول میزکار: ماژولی که
 * کارت ثبت می‌کند نباید با حذف پوشه Workspace از کار بیفتد (قاعده ۲).
 *
 * فهرست خالی یعنی «در این نما چیزی برای نشان‌دادن ندارم»؛ کارت ساخته نمی‌شود.
 */
interface WorkspaceWidgetSource
{
    public const TAG = 'workspace.widget_sources';

    /** @return list<WorkspaceWidget> */
    public function widgets(User $user, WorkspaceView $view): array;
}
