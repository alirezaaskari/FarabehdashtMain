<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Modules\Identity\Services\OtpService;
use App\Modules\Identity\Services\PermissionResolver;
use App\Modules\Identity\Services\Sms\LogSmsSender;
use App\Modules\Identity\Services\Sms\MelipayamakSmsSender;
use App\Support\Modules\ModuleProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class IdentityServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Identity';
    }

    protected function registerModule(): void
    {
        // Identity صاحب ورود و خروج است؛ پس نگهبان نشست‌دار وب را همین‌جا
        // به قرارداد فریم‌ورک می‌بندد تا Action و Controllerها آن را مستقیم
        // تزریق کنند و به نام گارد گره نخورند.
        $this->app->bind(StatefulGuard::class, function (): StatefulGuard {
            $guard = $this->app->make(AuthFactory::class)->guard('web');

            assert($guard instanceof StatefulGuard);

            return $guard;
        });

        $this->app->singleton(SmsSender::class, function (): SmsSender {
            $driver = (string) config('identity.sms.driver', 'log');

            return match ($driver) {
                'log' => new LogSmsSender($this->app->make(LoggerInterface::class)),
                'melipayamak' => new MelipayamakSmsSender(
                    $this->app->make(Http::class),
                    $this->app->make(LoggerInterface::class),
                    (array) config('identity.sms.melipayamak', []),
                ),
                default => throw new InvalidArgumentException("درایور پیامک ناشناخته: {$driver}"),
            };
        });

        $this->app->singleton(OtpService::class, fn (): OtpService => new OtpService(
            $this->app->make(SmsSender::class),
            $this->app->make(Hasher::class),
            (array) config('identity.otp', []),
        ));

        $this->app->singleton(PermissionResolver::class, fn (): PermissionResolver => new PermissionResolver(
            (array) config('identity.permissions.base', []),
            (array) config('identity.permissions.profiles', []),
            (array) config('identity.permissions.labels', []),
        ));
    }

    protected function bootModule(): void
    {
        $this->registerGates();
        $this->registerAuthRedirects();
    }

    /**
     * مقصد کاربر واردشده‌ای که به صفحه ورود می‌آید.
     *
     * مهمان را میان‌افزار auth خودش به مسیر قراردادی login می‌فرستد؛ اینجا و
     * نه در bootstrap/app.php نوشته شده تا هسته برنامه به مسیرهای این ماژول
     * گره نخورد و حذف ماژول یک‌خطی بماند.
     */
    private function registerAuthRedirects(): void
    {
        RedirectIfAuthenticated::redirectUsing(static fn (): string => route('identity.profiles'));
    }

    /**
     * هر مجوز شناخته‌شده یک Gate می‌شود، تا در کد و قالب با
     * $user->can('products.manage') یا @can بررسی شود.
     */
    private function registerGates(): void
    {
        $resolver = $this->app->make(PermissionResolver::class);

        foreach ($resolver->known() as $permission) {
            Gate::define(
                $permission,
                static fn (User $user): bool => $resolver->allows($user, $permission),
            );
        }
    }
}
