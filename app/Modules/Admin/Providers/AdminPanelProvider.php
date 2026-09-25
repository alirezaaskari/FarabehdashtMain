<?php

declare(strict_types=1);

namespace App\Modules\Admin\Providers;

use App\Modules\Admin\Filament\Pages\AuditLogPage;
use App\Modules\Admin\Filament\Pages\Dashboard;
use App\Modules\Admin\Services\LocalInitialsAvatar;
use App\Support\Admin\NavigationGroup;
use App\Support\Modules\ModuleRegistry;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * پنل مدیریت.
 *
 * مسیر از پیکربندی می‌آید و نه سخت‌کدشده (DEC-09): مسیر غیرقابل‌حدس، قابل
 * تغییر با یک متغیر محیطی. امنیت واقعی از مجوزهاست؛ این فقط حجم حمله کور
 * ربات‌ها را کم می‌کند.
 *
 * راست‌چینی خودبه‌خود کار می‌کند: Filament جهت را از ترجمه
 * `filament-panels::layout.direction` می‌خواند و ترجمه فارسی‌اش `rtl` است.
 * پس شرط لازم فقط این است که `APP_LOCALE` روی `fa` باشد.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // کشف ماژول‌ها عمداً **آخر** زنجیره است: `discoverPages` شناسه پنل را
        // لازم دارد و اگر پیش از `id()` صدا زده شود، هر درخواست با
        // «A panel has been registered without an id()» می‌افتد.
        return $this->discoverModuleUi(
            $panel
                ->id('fbh')
                ->path((string) config('admin.path', 'fbh-panel'))
                ->brandName((string) config('app.name'))
                // پوسته خودمان همان CSS پایه Filament است به‌اضافه کلاس‌های Tailwind
                // صفحه‌های سفارشی ماژول‌ها. بدون آن، CSS آماده Filament این
                // کلاس‌ها را ندارد و فرم‌ها و فهرست‌های پنل بی‌فاصله و درهم‌اند.
                // خروجی‌اش مثل بقیه دارایی‌ها در public/build کامیت می‌شود.
                ->viteTheme('resources/css/filament/fbh/theme.css')
                // فونت خودمیزبان.
                ->font('Vazirmatn FBH', url: '/fonts/fbh/vazirmatn.css', provider: LocalFontProvider::class)
                // آواتار روی همین سرور ساخته می‌شود؛ پیش‌فرض Filament نام کاربر را
                // به ui-avatars.com می‌فرستد.
                ->defaultAvatarProvider(LocalInitialsAvatar::class)
                // `->login()` عمداً نیست: فرم Filament ایمیل و رمز می‌خواهد و حساب
                // مدیر رمز ندارد. بدون آن، مهمان به `route('login')` سایت (ورود با
                // کد یک‌بارمصرف) می‌رود و پس از ورود به همین پنل برمی‌گردد.
                ->colors(['primary' => self::PRIMARY])
                ->defaultThemeMode(ThemeMode::Light)
                ->sidebarCollapsibleOnDesktop()
                ->navigationGroups(NavigationGroup::class)
                // قاعده لایه طراحی: پهنای محتوا محدود نمی‌شود.
                ->maxContentWidth(Width::Full)
                ->pages([Dashboard::class, AuditLogPage::class])
                // راهنمای «این صفحه به چه کار می‌آید؟» زیر عنوان هر صفحه‌ای که در
                // config/help.php متن دارد. کلید، نام مسیر صفحه است و از کلاس صفحه
                // (scope) ساخته می‌شود، نه از درخواست: در به‌روزرسانی Livewire
                // مسیر درخواست `livewire.update` است و راهنما ناپدید می‌شد.
                ->renderHook(PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE, self::pageHelp(...))
                ->middleware([
                    EncryptCookies::class,
                    AddQueuedCookiesToResponse::class,
                    StartSession::class,
                    AuthenticateSession::class,
                    ShareErrorsFromSession::class,
                    VerifyCsrfToken::class,
                    SubstituteBindings::class,
                    DisableBladeIconComponents::class,
                    DispatchServingFilamentEvent::class,
                ])
                // تنها نگهبان ورود، `PanelGatekeeper` است که Filament از راه
                // `User::canAccessPanel()` صدایش می‌زند. میان‌افزار دوم اضافه
                // نمی‌شود: دو نگهبان برای یک کار یعنی روزی یکی‌شان عوض می‌شود و
                // دیگری بی‌سروصدا بی‌اثر می‌ماند.
                ->authMiddleware([Authenticate::class]),
        );
    }

    /**
     * صفحه‌ها و منابع Filament هر ماژول فعال.
     *
     * پنل پوشه‌ها را می‌پیماید و هیچ کلاسی را نام نمی‌برد: این تنها راهی است
     * که یک ماژول بتواند صفحه مدیریتی داشته باشد بدون اینکه Admin مدلش را
     * import کند (قاعده ۱). ماژولی که برداشته شود، صفحه‌اش هم خودبه‌خود
     * می‌رود.
     */
    private function discoverModuleUi(Panel $panel): Panel
    {
        $registry = app(ModuleRegistry::class);

        foreach ($registry->enabled() as $module) {
            $namespace = sprintf('%s\\%s\\Filament', $registry->rootNamespace(), $module);

            $pages = $registry->path($module, 'Filament/Pages');

            if (is_dir($pages)) {
                $panel = $panel->discoverPages(in: $pages, for: $namespace.'\\Pages');
            }

            $resources = $registry->path($module, 'Filament/Resources');

            if (is_dir($resources)) {
                $panel = $panel->discoverResources(in: $resources, for: $namespace.'\\Resources');
            }
        }

        return $panel;
    }

    /** @param  list<string>  $scopes */
    private static function pageHelp(array $scopes): ?View
    {
        $page = $scopes[0] ?? null;

        if ($page === null || ! method_exists($page, 'getRouteName')) {
            return null;
        }

        $route = $page::getRouteName();
        $help = config('help.panel')[$route] ?? null;

        return is_array($help)
            ? view('admin::filament.hooks.page-help', ['route' => $route, 'help' => $help])
            : null;
    }

    /**
     * سایه‌های رنگ اصلی برند.
     *
     * Filament پالت عددی می‌خواهد و توکن CSS نمی‌فهمد، پس این تنها جایی است
     * که رنگ به‌صورت عدد نوشته می‌شود. مقدارها از `resources/css/tokens.css`
     * گرفته شده‌اند تا پنل و سایت یک رنگ باشند.
     */
    private const PRIMARY = [
        50 => '#e8f0ed',
        100 => '#c7ddd6',
        200 => '#a1c7bc',
        300 => '#7ab1a2',
        400 => '#5a9c8d',
        500 => '#0f5f52',
        600 => '#0d5449',
        700 => '#0a473e',
        800 => '#083a33',
        900 => '#062b26',
        950 => '#041a17',
    ];
}
