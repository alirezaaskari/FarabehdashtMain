<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Models\User;
use App\Modules\Workspace\Domain\WorkspacePreference;
use App\Support\Workspace\WorkspaceView;

/**
 * نماهای میزکار یک کاربر: «شخصی» و یکی به ازای هر پروفایل فعال.
 *
 * نمای ذخیره‌شده‌ای که دیگر در دسترس نیست (پروفایل غیرفعال یا تعلیق شده)
 * بی‌سروصدا به «شخصی» برمی‌گردد؛ کاربر هرگز به میزکار نقشی که ندارد نمی‌رسد.
 */
final readonly class WorkspaceViews
{
    /** @return list<WorkspaceView> */
    public function available(User $user): array
    {
        $views = [WorkspaceView::personal()];

        foreach ($user->activeProfiles() as $profile) {
            $views[] = WorkspaceView::profile($profile->type->value, $profile->type->label());
        }

        return $views;
    }

    public function current(User $user): WorkspaceView
    {
        $saved = WorkspacePreference::query()->where('user_id', $user->getKey())->value('view');

        return $this->find($user, is_string($saved) ? $saved : WorkspaceView::PERSONAL) ?? WorkspaceView::personal();
    }

    public function find(User $user, string $key): ?WorkspaceView
    {
        foreach ($this->available($user) as $view) {
            if ($view->key === $key) {
                return $view;
            }
        }

        return null;
    }
}
