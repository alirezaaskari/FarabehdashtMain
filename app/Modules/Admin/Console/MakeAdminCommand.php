<?php

declare(strict_types=1);

namespace App\Modules\Admin\Console;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Events\AdminEmailLoginEnabled;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Support\Mobile;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * ساخت یا ارتقای حساب مدیر.
 *
 * تنها راه ساخت اولین مدیر ارشد. عمداً فقط از خط فرمان کار می‌کند و هیچ
 * مسیر وبی ندارد: صفحه‌ای که بتواند مدیر بسازد، دیر یا زود پیدا می‌شود.
 *
 * DEC-16.
 */
final class MakeAdminCommand extends Command
{
    protected $signature = 'fbh:make-admin
        {mobile : شماره موبایل حساب}
        {--role=super : نقش مدیریتی (super، content، finance، jobs)}
        {--email= : ایمیل تأییدشده برای ورود مدیر با کد ایمیلی}';

    protected $description = 'اعطای نقش مدیریتی به یک حساب؛ اگر حساب نباشد ساخته می‌شود.';

    public function handle(GrantAdminRole $grant, Dispatcher $events): int
    {
        $mobile = Mobile::tryFromInput((string) $this->argument('mobile'));

        if (! $mobile instanceof Mobile) {
            $this->components->error('شماره موبایل معتبر نیست.');

            return self::FAILURE;
        }

        $role = AdminRole::tryFrom((string) $this->option('role'));

        if (! $role instanceof AdminRole) {
            $this->components->error('نقش نامعتبر است. یکی از: '.implode('، ', array_column(AdminRole::cases(), 'value')));

            return self::FAILURE;
        }

        $email = $this->email();

        if ($email === false) {
            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['mobile' => $mobile->value]);

        if ($email !== null && User::query()->where('email', $email)->where('mobile', '!=', $mobile->value)->exists()) {
            $this->components->error('این ایمیل روی حساب دیگری ثبت است.');

            return self::FAILURE;
        }
        $isNew = ! $user->exists;

        if ($isNew) {
            $user->status = UserStatus::Active;
            $user->mobile_verified_at = now();
            $user->save();
        }

        $grant->handle($user, $role);

        // ایمیلی که مدیر ارشد از خط فرمان می‌دهد تأییدشده حساب می‌شود؛ این
        // تنها راه تأیید است، چون ورود ایمیلی به پنل مدیریت راه می‌دهد.
        if ($email !== null) {
            $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();
            $events->dispatch(new AdminEmailLoginEnabled($user));
            $this->components->info('ورود با کد ایمیلی برای این حساب فعال شد.');
        }

        $this->components->info(sprintf(
            '%s نقش «%s» گرفت.',
            $isNew ? 'حساب تازه ساخته شد و' : 'حساب موجود',
            $role->label(),
        ));

        $this->components->warn('ورود با همین شماره و کد یک‌بارمصرف انجام می‌شود؛ رمز عبوری ساخته نشد.');

        return self::SUCCESS;
    }

    /** @return string|null|false ایمیل کوچک‌حرف، null اگر داده نشده، false اگر نامعتبر */
    private function email(): string|null|false
    {
        $email = $this->option('email');

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        $email = mb_strtolower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('ایمیل معتبر نیست.');

            return false;
        }

        return $email;
    }
}
