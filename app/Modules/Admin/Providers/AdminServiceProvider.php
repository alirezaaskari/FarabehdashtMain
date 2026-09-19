<?php

declare(strict_types=1);

namespace App\Modules\Admin\Providers;

use App\Contracts\PanelAccess;
use App\Models\User;
use App\Modules\Admin\Console\MakeAdminCommand;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Http\Middleware\RequireAdminAbility;
use App\Modules\Admin\Services\AdminAccess;
use App\Modules\Admin\Services\ApprovalQueue;
use App\Modules\Admin\Services\Impersonation;
use App\Modules\Admin\Services\PanelGatekeeper;
use App\Support\Modules\ModuleProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;

/**
 * ماژول مرکز مدیریت.
 *
 * پنل Filament را به‌صورت یک PanelProvider جدا ثبت می‌کند تا با برداشتن این
 * ماژول از `config/modules.php`، پنل هم کامل برود.
 */
final class AdminServiceProvider extends ModuleProvider
{
    /** برچسب کانتینر برای ماژول‌هایی که مورد منتظر تأیید دارند. */
    public const APPROVAL_SOURCES = 'admin.approval_sources';

    public function moduleName(): string
    {
        return 'Admin';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(AdminAccess::class);
        $this->app->singleton(PanelAccess::class, PanelGatekeeper::class);

        $this->app->singleton(ApprovalQueue::class, fn (): ApprovalQueue => new ApprovalQueue(
            $this->app->tagged(self::APPROVAL_SOURCES),
            $this->app->make(AdminAccess::class),
        ));

        $this->app->tag([], self::APPROVAL_SOURCES);

        $this->app->singleton(Impersonation::class);

        $this->app->register(AdminPanelProvider::class);
    }

    protected function bootModule(): void
    {
        $this->registerGates();
        $this->registerMiddlewareAlias();

        if ($this->app->runningInConsole()) {
            $this->commands([MakeAdminCommand::class]);
        }
    }

    /**
     * هر توانایی مدیریتی یک Gate می‌شود.
     *
     * پیشوند `admin.` عمدی است: در کد و قالب هیچ‌وقت با مجوز کاربری
     * (`products.manage` و مانند آن) اشتباه گرفته نمی‌شود.
     */
    private function registerGates(): void
    {
        $access = $this->app->make(AdminAccess::class);

        foreach (AdminRole::allAbilities() as $ability) {
            Gate::define($ability, static fn (User $user): bool => $access->allows($user, $ability));
        }
    }

    private function registerMiddlewareAlias(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('admin.ability', RequireAdminAbility::class);
    }

    /** @return list<class-string> */
    public function provides(): array
    {
        return [AdminAccess::class, ApprovalQueue::class, Impersonation::class, PanelAccess::class];
    }
}
