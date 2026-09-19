<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\PersianDigits;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerBladeDirectives();
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
