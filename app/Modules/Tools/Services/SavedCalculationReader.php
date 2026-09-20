<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Contracts\CalculationReader;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Measurement\StoredCalculation;

/**
 * پیاده‌سازی قرارداد `CalculationReader` برای ماژول‌های دیگر.
 *
 * این تنها دری است که ماژول پروژه‌ها از آن به محاسبه‌های ذخیره‌شده نگاه
 * می‌کند؛ مدل `SavedCalculation` هرگز بیرون از این ماژول دیده نمی‌شود
 * (قاعده ۱).
 *
 * «خروجی اصلی» را همین‌جا تعیین می‌کنیم چون فقط این ماژول می‌داند هر فرمول
 * چه می‌دهد: اولین خروجیِ اعلام‌شده. ترتیب `outputs()` در هر فرمول آگاهانه
 * چیده شده و نخستین کلید، همان عددی است که کاربر دنبالش بوده.
 */
final readonly class SavedCalculationReader implements CalculationReader
{
    public function findForUser(string $uuid, int $userId): ?StoredCalculation
    {
        $saved = SavedCalculation::query()
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->first();

        if ($saved === null) {
            return null;
        }

        $headlineKey = array_key_first($saved->outputs);

        if ($headlineKey === null) {
            return null;
        }

        // شکل ذخیره‌شده JSON است و نه یک شیء تایپ‌دار؛ ردیف قدیمی ممکن است
        // کلید واحد نداشته باشد. پس دفاعی خوانده می‌شود، نه با فرض.
        /** @var array<string, mixed> $headline */
        $headline = $saved->outputs[$headlineKey];

        return new StoredCalculation(
            uuid: $saved->uuid,
            toolSlug: $saved->tool_slug,
            formulaId: $saved->formula_id,
            formulaVersion: $saved->formula_version,
            label: $saved->label,
            headlineKey: (string) $headlineKey,
            headlineValue: (float) ($headline['value'] ?? 0.0),
            headlineUnit: (string) ($headline['unit'] ?? ''),
        );
    }
}
