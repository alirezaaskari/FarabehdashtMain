<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Filament\Pages;

use App\Modules\Commerce\Actions\PublishProduct;
use App\Modules\Commerce\Actions\RejectProduct;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * بررسی محصولات در انتظار انتشار.
 *
 * دکمه‌ها همان اکشن‌های {@see PublishProduct} و {@see RejectProduct} را صدا
 * می‌زنند، نه یک به‌روزرسانی مستقیم؛ اگر قاعده «محصول فروشنده بدون تأیید
 * مدیر منتشر نمی‌شود» از راه پنل دور زدنی بود، اصلاً قاعده نبود — همان الگوی
 * `ContentHealthPage` دانشنامه.
 */
final class ProductReviewPage extends Page
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $slug = 'commerce-review';

    protected static ?int $navigationSort = 47;

    protected string $view = 'commerce::filament.pages.review';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $rejectNotes = [];

    public static function getNavigationLabel(): string
    {
        return 'بررسی محصولات';
    }

    public function getTitle(): string
    {
        return 'بررسی محصولات فروشگاه';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function publish(int $id, PublishProduct $publish): void
    {
        $product = Product::query()->find($id);

        if ($product === null) {
            return;
        }

        try {
            $publish->handle($product, $this->actorId());

            Notification::make()->title('منتشر شد: '.$product->title)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('منتشر نشد')->body($exception->getMessage())->danger()->send();
        }

        $this->load();
    }

    public function reject(int $id, RejectProduct $reject): void
    {
        $product = Product::query()->find($id);
        $note = trim($this->rejectNotes[$id] ?? '');

        if ($product === null || $this->actorId() === null) {
            return;
        }

        try {
            $reject->handle($product, $this->actorId(), $note);

            Notification::make()->title('رد شد: '.$product->title)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('رد نشد')->body($exception->getMessage())->danger()->send();
        }

        unset($this->rejectNotes[$id]);
        $this->load();
    }

    private function load(): void
    {
        $this->rows = Product::query()
            ->where('status', ProductStatus::InReview->value)
            ->with('vendor')
            ->oldest('updated_at')
            ->get()
            ->map(static fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'vendor' => $product->vendor->name ?? ('کاربر #'.$product->vendor_user_id),
                'price' => $product->price()->format(),
                'versions' => $product->versions->count(),
            ])
            ->all();
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
