<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Linking\LinkTarget;

/**
 * ماژولی که صفحه‌هایش می‌توانند مقصد پیوند داخلی خودکار باشند.
 *
 * هر مقصد چند عبارت دارد (عنوان، نام انگلیسی، شماره CAS)؛ هر جای متن
 * دانشنامه که یکی از آن‌ها دقیقاً بیاید، به همان صفحه پیوند می‌خورد.
 * ماژول با برچسب {@see self::TAG} ثبت می‌شود و موتور پیوند نمی‌داند چه
 * ماژول‌هایی وجود دارند.
 */
interface LinkTargetSource
{
    public const TAG = 'linking.targets';

    /** @return iterable<LinkTarget> */
    public function linkTargets(): iterable;
}
