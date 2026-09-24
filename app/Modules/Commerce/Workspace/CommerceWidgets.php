<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Product;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * دو کارت: «خریدهای من» روی نمای شخصی و «محصولات من» روی نمای فروشنده.
 */
final readonly class CommerceWidgets implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    /** مقدار نوع پروفایل فروشنده — رشته، چون Enum ماژول هویت import نمی‌شود. */
    private const VENDOR = 'vendor';

    public function widgets(User $user, WorkspaceView $view): array
    {
        return match (true) {
            $view->isPersonal() => $this->purchases($user),
            $view->is(self::VENDOR) => $this->products($user),
            default => [],
        };
    }

    /** @return list<WorkspaceWidget> */
    private function purchases(User $user): array
    {
        if (! Route::has('commerce.show') || ! Route::has('commerce.index')) {
            return [];
        }

        $items = OrderItem::query()
            ->whereHas('order', static function (Builder $order) use ($user): void {
                $order->where('buyer_user_id', $user->getKey())
                    ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::PartiallyRefunded->value]);
            })
            ->with('product')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return [new WorkspaceWidget(
            key: 'purchases',
            icon: 'bag',
            title: 'خریدهای من',
            order: 30,
            rows: $items
                ->map(static fn (OrderItem $item): WidgetRow => new WidgetRow(
                    label: $item->product->title,
                    url: route('commerce.show', $item->product->slug),
                    meta: 'دانلود از صفحه محصول',
                ))
                ->values()
                ->all(),
            empty: 'فایل‌ها و قالب‌هایی که می‌خرید این‌جا می‌مانند و هر نسخه تازه‌شان در دسترس است.',
            actionUrl: route('commerce.index'),
            actionLabel: 'رفتن به فروشگاه',
        )];
    }

    /** @return list<WorkspaceWidget> */
    private function products(User $user): array
    {
        if (! Route::has('commerce.vendor.products.index')) {
            return [];
        }

        $query = Product::query()->ownedBy((int) $user->getKey());

        return [new WorkspaceWidget(
            key: 'vendor-products',
            icon: 'upload',
            title: 'محصولات من',
            order: 10,
            stats: [
                new WidgetStat('منتشرشده', PersianDigits::from((clone $query)->where('status', ProductStatus::Published->value)->count())),
                new WidgetStat('در صف بررسی', PersianDigits::from((clone $query)->where('status', ProductStatus::InReview->value)->count())),
            ],
            rows: $query->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(static fn (Product $product): WidgetRow => new WidgetRow(
                    label: $product->title,
                    url: Route::has('commerce.vendor.products.edit') ? route('commerce.vendor.products.edit', $product) : null,
                    meta: $product->status->label(),
                ))
                ->values()
                ->all(),
            empty: 'نخستین فایل تخصصی‌تان را بارگذاری کنید؛ پس از تأیید مدیر منتشر می‌شود.',
            actionUrl: route('commerce.vendor.products.index'),
            actionLabel: 'مدیریت محصولات',
        )];
    }
}
