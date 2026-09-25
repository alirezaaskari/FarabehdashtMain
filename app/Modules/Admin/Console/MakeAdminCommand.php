<?php

declare(strict_types=1);

namespace App\Modules\Admin\Console;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Events\AdminEmailLoginEnabled;
use App\Modules\Admin\Events\AdminPasswordSet;
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
        {--email= : ایمیل تأییدشده برای ورود مدیر با ایمیل و رمز}
        {--password : پرسیدن رمز عبور تازه برای ورود با ایمیل}';

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

        $password = $this->option('password') ? $this->password() : null;

        if ($password === false) {
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

        // ایمیل و رمزی که مدیر ارشد از خط فرمان می‌دهد تنها راه ثبتشان است،
        // چون ورود با ایمیل به پنل مدیریت راه می‌دهد. رمز هش می‌شود (cast مدل).
        if ($email !== null) {
            $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();
            $events->dispatch(new AdminEmailLoginEnabled($user));
        }

        if ($password !== null) {
            $user->forceFill(['password' => $password])->save();
            $events->dispatch(new AdminPasswordSet($user));
        }

        if ($user->email_verified_at !== null && $user->password !== null) {
            $this->components->info('ورود با ایمیل و رمز عبور برای این حساب فعال است: '.route('identity.email.show'));
        } elseif ($email !== null || $password !== null) {
            $this->components->warn('برای ورود با ایمیل هر دو لازم است: --email و --password.');
        }

        $this->components->info(sprintf(
            '%s نقش «%s» گرفت.',
            $isNew ? 'حساب تازه ساخته شد و' : 'حساب موجود',
            $role->label(),
        ));

        return self::SUCCESS;
    }

    /**
     * رمز از ورودی پنهان پرسیده می‌شود، نه از گزینه خط فرمان: گزینه در
     * تاریخچه شل و فهرست پردازه‌ها می‌ماند.
     *
     * @return string|false رمز، یا false اگر نپذیرفت
     */
    private function password(): string|false
    {
        $min = (int) config('identity.admin_email_login.min_password_length', 12);
        $password = (string) $this->secret(sprintf('رمز عبور تازه (دست‌کم %d نویسه)', $min));

        if (mb_strlen($password) < $min) {
            $this->components->error(sprintf('رمز باید دست‌کم %d نویسه باشد.', $min));

            return false;
        }

        if ((string) $this->secret('تکرار رمز عبور') !== $password) {
            $this->components->error('دو رمز یکی نیستند.');

            return false;
        }

        return $password;
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
