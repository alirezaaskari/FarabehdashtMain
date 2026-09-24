<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Providers;

use App\Contracts\CommissionCalculator;
use App\Contracts\PaymentGateway;
use App\Contracts\SearchSource;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Commerce\Admin\PendingProducts;
use App\Modules\Commerce\Home\ProductHighlights;
use App\Modules\Commerce\Search\ProductSearch;
use App\Modules\Commerce\Services\CommissionService;
use App\Modules\Commerce\Services\Payments\ZarinPalGateway;
use App\Modules\Commerce\Workspace\CommerceWidgets;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Support\Modules\ModuleProvider;
use Illuminate\Http\Client\Factory as Http;
use InvalidArgumentException;

/**
 * ماژول تجارت.
 *
 * `CommissionCalculator` و `PaymentGateway` تنها درهایی هستند که ماژول‌های
 * دیگر (مثل دوره‌ها، بخش ۱۳) برای فروش و کمیسیون از آن‌ها عبور می‌کنند.
 */
final class CommerceServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Commerce';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(CommissionCalculator::class, CommissionService::class);

        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $driver = (string) config('commerce.payment.driver', 'zarinpal');

            return match ($driver) {
                'zarinpal' => new ZarinPalGateway(
                    $this->app->make(Http::class),
                    (array) config('commerce.payment.zarinpal', []),
                ),
                default => throw new InvalidArgumentException("درایور درگاه پرداخت ناشناخته: {$driver}"),
            };
        });

        $this->app->tag([PendingProducts::class], AdminServiceProvider::APPROVAL_SOURCES);

        $this->app->tag([ProductHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);

        // برچسب‌ها روی خود قراردادها هستند؛ حذف ماژول میزکار این ماژول را نمی‌شکند.
        $this->app->tag([ProductSearch::class], SearchSource::TAG);
        $this->app->tag([CommerceWidgets::class], WorkspaceWidgetSource::TAG);
    }
}
