<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Events\AccountUpdated;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * کاربر نام و ایمیل خودش را تکمیل یا اصلاح کرد.
 *
 * موبایل این‌جا عوض نمی‌شود: شناسه ورود است و تغییرش تأیید کد روی شماره
 * تازه می‌خواهد. ایمیل اختیاری است و فقط برای رسید و اطلاع‌رسانی است.
 */
final readonly class UpdateAccount
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $user, string $name, ?string $email): User
    {
        $email = $email === null || trim($email) === '' ? null : mb_strtolower(trim($email));

        $user->fill(['name' => trim($name), 'email' => $email]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $changed = array_keys($user->getDirty());

        if ($changed === []) {
            return $user;
        }

        $user->save();

        $this->events->dispatch(new AccountUpdated($user, array_values(array_diff($changed, ['email_verified_at']))));

        return $user;
    }
}
