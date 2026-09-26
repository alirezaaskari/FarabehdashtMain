<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Bundles\BundleComponent;

/**
 * یک نوع جزء بسته راه‌حل (بخش ۱۸-۸): فایل فروشگاه، دوره یا ماه‌های Pro.
 *
 * بسته‌ها مدل هیچ ماژولی را نمی‌شناسند (قاعده ۱). هر ماژول صاحب جزء این
 * قرارداد را با برچسب {@see TAG} ثبت می‌کند: فهرست گزینه‌ها برای پنل، قیمت
 * و صاحب هر جزء برای تقسیم پول، و اعطای جزء پس از پرداخت بسته. با حذف یک
 * ماژول، جزء‌های آن نوع فقط «در دسترس نیست» می‌شوند.
 */
interface BundleComponentSource
{
    public const TAG = 'bundles.component_sources';

    /** شناسه نوع، مثل «product»؛ در ردیف بسته ذخیره می‌شود. */
    public function kind(): string;

    /** نام نوع برای مدیر، مثل «فایل فروشگاه». */
    public function label(): string;

    /** @return list<BundleComponent> گزینه‌های قابل‌افزودن در پنل */
    public function options(): array;

    /** جزء فعلی با قیمت امروز، یا null اگر دیگر فروختنی نیست. */
    public function find(string $ref): ?BundleComponent;

    /** آیا کاربر این جزء را پیش‌تر دارد. */
    public function owns(int $userId, string $ref): bool;

    /**
     * دادن جزء به خریدار بسته، بی‌پرداخت جدا؛ پول بسته را ماژول بسته‌ها در
     * دفتر کل ثبت کرده است. $purchaseUuid فقط برای ردگیری است.
     */
    public function grant(int $userId, string $ref, string $purchaseUuid): void;
}
