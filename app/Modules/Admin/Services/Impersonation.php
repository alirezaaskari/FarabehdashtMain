<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Events\ImpersonationStarted;
use App\Modules\Admin\Events\ImpersonationStopped;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use RuntimeException;

/**
 * مشاهده میزکار از چشم یک کاربر.
 *
 * سه قاعده که در کد اعمال شده‌اند، نه در دستورالعمل:
 *
 * ۱. بدون دلیل نوشته‌شده شروع نمی‌شود، و همان دلیل در دفتر رویداد می‌نشیند.
 * ۲. در این حالت هیچ عملیات مالی مجاز نیست (`isActive()` را همه‌جای مالی
 *    بررسی می‌کنند). مدیری که با حساب کاربر پول جابه‌جا کند، ردش گم می‌شود.
 * ۳. مدیر اصلی در نشست نگه داشته می‌شود تا بازگشت همیشه ممکن باشد، حتی اگر
 *    حساب هدف وسط کار معلق شود.
 */
final readonly class Impersonation
{
    private const SESSION_KEY = 'admin.impersonator_id';

    private const REASON_KEY = 'admin.impersonation_reason';

    public function __construct(
        private StatefulGuard $guard,
        private Session $session,
        private Dispatcher $events,
        private AdminAccess $access,
    ) {}

    /**
     * @throws RuntimeException اگر مجوز نباشد، دلیل خالی باشد یا هدف خودِ مدیر باشد
     */
    public function start(User $admin, User $target, string $reason): void
    {
        if (! $this->access->allows($admin, 'admin.impersonate')) {
            throw new RuntimeException('برای مشاهده به‌عنوان کاربر، مجوز لازم را ندارید.');
        }

        if (trim($reason) === '') {
            throw new RuntimeException('دلیل مشاهده به‌عنوان کاربر اجباری است.');
        }

        if ($admin->getKey() === $target->getKey()) {
            throw new RuntimeException('مشاهده به‌عنوان خودتان معنی ندارد.');
        }

        if ($this->isActive()) {
            throw new RuntimeException('یک مشاهده فعال است؛ اول از آن خارج شوید.');
        }

        $this->session->put(self::SESSION_KEY, $admin->getKey());
        $this->session->put(self::REASON_KEY, $reason);

        $this->guard->login($target);

        $this->events->dispatch(new ImpersonationStarted($target, (int) $admin->getKey(), $reason));
    }

    /**
     * @throws RuntimeException اگر مشاهده‌ای فعال نباشد یا حساب مدیر از بین رفته باشد
     */
    public function stop(): User
    {
        $adminId = $this->impersonatorId();

        if ($adminId === null) {
            throw new RuntimeException('مشاهده‌ای فعال نیست.');
        }

        $target = $this->guard->user();
        $admin = User::query()->find($adminId);

        if (! $admin instanceof User) {
            throw new RuntimeException('حساب مدیر پیدا نشد.');
        }

        $this->session->forget([self::SESSION_KEY, self::REASON_KEY]);

        $this->guard->login($admin);

        if ($target instanceof User) {
            $this->events->dispatch(new ImpersonationStopped($target, (int) $admin->getKey()));
        }

        return $admin;
    }

    public function isActive(): bool
    {
        return $this->impersonatorId() !== null;
    }

    public function impersonatorId(): ?int
    {
        $id = $this->session->get(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    public function reason(): ?string
    {
        $reason = $this->session->get(self::REASON_KEY);

        return is_string($reason) ? $reason : null;
    }

    /**
     * نگهبان عملیات مالی.
     *
     * هر Action مالی این را در ابتدای کارش صدا می‌زند.
     *
     * @throws RuntimeException وقتی مشاهده فعال است
     */
    public function guardAgainstFinancialAction(): void
    {
        if ($this->isActive()) {
            throw new RuntimeException('در حالت «مشاهده به‌عنوان کاربر» عملیات مالی مجاز نیست.');
        }
    }
}
