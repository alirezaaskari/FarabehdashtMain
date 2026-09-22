<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * نوع حد مواجهه.
 *
 * چهار مفهوم متفاوت‌اند و جای هم را نمی‌گیرند. مهم‌ترینشان برای این سیستم:
 * **IDLH حد مجاز مواجهه نیست.** غلظتی است که فرار بدون آسیب برگشت‌ناپذیر را
 * ناممکن می‌کند و معیار انتخاب تجهیز حفاظت تنفسی در وضعیت اضطراری است. اگر
 * جایی مثل TWA به‌کار برود، یک خطای تفسیری جدی است — و همین است که
 * `comparableWithMeasurement()` را لازم می‌کند.
 */
enum LimitType: string
{
    case Twa = 'twa';
    case Stel = 'stel';
    case Ceiling = 'ceiling';
    case Idlh = 'idlh';

    public function label(): string
    {
        return match ($this) {
            self::Twa => 'TWA — میانگین وزنی‌زمانی',
            self::Stel => 'STEL — مواجهه کوتاه‌مدت',
            self::Ceiling => 'Ceiling — حد سقف',
            self::Idlh => 'IDLH — خطر آنی برای جان',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Twa => 'TWA',
            self::Stel => 'STEL',
            self::Ceiling => 'Ceiling',
            self::Idlh => 'IDLH',
        };
    }

    /**
     * آیا این حد با نتیجه یک اندازه‌گیری معمول مقایسه می‌شود.
     *
     * IDLH نه. به‌کاربردنش به‌جای TWA یا STEL خطای تفسیری است و رابط کاربری
     * باید همین را بگوید، نه اینکه چهار عدد را کنار هم بگذارد و سکوت کند.
     */
    public function comparableWithMeasurement(): bool
    {
        return $this !== self::Idlh;
    }

    public function tone(): string
    {
        return match ($this) {
            self::Idlh => 'danger',
            self::Ceiling => 'caution',
            self::Twa, self::Stel => 'neutral',
        };
    }
}
