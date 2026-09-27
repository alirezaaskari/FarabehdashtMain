<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Settings\Tunable;

/**
 * قیمت‌ها و مدت‌هایی که مدیر ارشد از پنل «قیمت‌ها و زمان‌ها» عوض می‌کند.
 *
 * هر ماژول عددهای خودش را با همان کلید config اعلام می‌کند و کلاسش را با
 * برچسب `TAG` ثبت می‌کند. Core مقدار ذخیره‌شده مدیر را روی همان کلید config
 * می‌نشاند، پس ماژول مثل قبل `config()` را می‌خواند و از جدول تنظیمات خبر ندارد.
 */
interface TunableSource
{
    public const TAG = 'core.tunables';

    /** @return list<Tunable> */
    public function tunables(): array;
}
