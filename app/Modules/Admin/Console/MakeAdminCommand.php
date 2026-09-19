<?php

declare(strict_types=1);

namespace App\Modules\Admin\Console;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Support\Mobile;
use Illuminate\Console\Command;

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
        {--role=super : نقش مدیریتی (super، content، finance، jobs)}';

    protected $description = 'اعطای نقش مدیریتی به یک حساب؛ اگر حساب نباشد ساخته می‌شود.';

    public function handle(GrantAdminRole $grant): int
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

        $user = User::query()->firstOrNew(['mobile' => $mobile->value]);
        $isNew = ! $user->exists;

        if ($isNew) {
            $user->status = UserStatus::Active;
            $user->mobile_verified_at = now();
            $user->save();
        }

        $grant->handle($user, $role);

        $this->components->info(sprintf(
            '%s نقش «%s» گرفت.',
            $isNew ? 'حساب تازه ساخته شد و' : 'حساب موجود',
            $role->label(),
        ));

        $this->components->warn('ورود با همین شماره و کد یک‌بارمصرف انجام می‌شود؛ رمز عبوری ساخته نشد.');

        return self::SUCCESS;
    }
}
