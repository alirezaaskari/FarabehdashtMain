<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\EntitlementGate;
use App\Contracts\InternalLinker;
use App\Contracts\SubscriberDiscount;
use App\Support\Entitlement\NoDiscount;
use App\Support\Entitlement\OpenGate;
use App\Support\Linking\NoLinks;
use App\Support\PersianDigits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // پیش‌فرض لایه دسترسی: بدون ماژول درآمدزایی، همه‌چیز برای همه باز است.
        // ماژول Monetization این بایندینگ را بازنویسی می‌کند و چون
        // ModulesServiceProvider بعد از این Provider ثبت می‌شود، بازنویسی‌اش
        // برنده است. نتیجه: هیچ مصرف‌کننده‌ای لازم نیست شاخه «اگر ماژول نبود»
        // بنویسد.
        $this->app->singleton(EntitlementGate::class, OpenGate::class);
        $this->app->singleton(SubscriberDiscount::class, NoDiscount::class);

        // همان الگو برای پیوند داخلی: بدون ماژول Linking، متن بی‌پیوند.
        $this->app->singleton(InternalLinker::class, NoLinks::class);
    }

    public function boot(): void
    {
        $this->registerBladeDirectives();

        // بیرون از production: مقداردهی گروهی روی ستون خارج از fillable باید
        // خطا بدهد، نه بی‌سروصدا نادیده گرفته شود. کیف پول به همین تکیه می‌کند:
        // cached_balance_toman از fillable بیرون است تا هیچ فرم یا اکشنی جز
        // Wallet::applyLedgerEntry() نتواند تغییرش دهد؛ بدون این خط، تلاش
        // اشتباهی برای تغییرش ساکت شکست می‌خورد و باگ پنهان می‌ماند.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    /**
     * دستور «fa@» عدد را با ارقام فارسی چاپ می‌کند: fa(۲)@ → ۲
     *
     * برای عدد داخل جمله فارسی. مقدار اندازه‌گیری و شناسه از این مسیر
     * نمی‌گذرد و با نشانه data-numeric لاتین و چپ‌به‌راست می‌ماند.
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive(
            'fa',
            static fn (string $expression): string => '<?php echo e('.PersianDigits::class."::from({$expression})); ?>",
        );
    }
}
