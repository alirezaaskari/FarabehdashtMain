<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Models\User;
use App\Modules\Workspace\Domain\WorkspacePreference;
use App\Modules\Workspace\Services\WorkspaceViews;
use App\Support\Workspace\WorkspaceView;
use InvalidArgumentException;

/**
 * تغییر نمای میزکار.
 *
 * فقط به نمایی که کاربر واقعاً دارد: کسی که پروفایل فروشنده‌اش فعال نیست، با
 * دست‌کاری فرم به میزکار فروشنده نمی‌رسد.
 */
final readonly class SwitchWorkspaceView
{
    public function __construct(private WorkspaceViews $views) {}

    /** @throws InvalidArgumentException اگر این نما برای کاربر در دسترس نباشد */
    public function handle(User $user, string $key): WorkspaceView
    {
        $view = $this->views->find($user, $key)
            ?? throw new InvalidArgumentException('این نمای میزکار برای حساب شما فعال نیست.');

        WorkspacePreference::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['view' => $view->key],
        );

        return $view;
    }
}
