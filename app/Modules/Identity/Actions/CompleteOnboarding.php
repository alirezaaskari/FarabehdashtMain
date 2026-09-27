<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;

/**
 * پایان خوش‌آمد اولین ورود. نام اختیاری است؛ نامی که کاربر پیش‌تر در
 * «حساب من» گذاشته با خالی بودن این فرم پاک نمی‌شود.
 */
final readonly class CompleteOnboarding
{
    public function handle(User $user, ?string $name): void
    {
        $name = trim((string) $name);

        $user->forceFill([
            'onboarded_at' => $user->onboarded_at ?? now(),
            'name' => $name !== '' ? $name : $user->name,
        ])->save();
    }
}
