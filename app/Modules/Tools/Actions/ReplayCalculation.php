<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\StoredInputs;
use Farabehdasht\CalcEngine\Calculation;
use Farabehdasht\CalcEngine\Engine;

/**
 * اجرای دوباره یک محاسبه ذخیره‌شده — معیار پذیرش بخش ۷.
 *
 * عمداً نسخه ذخیره‌شده را پاس می‌دهد و نه آخرین نسخه. اگر فردا نسخه ۲ یک
 * رابطه منتشر شود، گزارش دیروز باید همان عددی را بدهد که در آن گزارش چاپ شده،
 * نه عدد تازه. این تفاوت، فرق بین «سابقه» و «حدس» است.
 */
final readonly class ReplayCalculation
{
    public function __construct(private Engine $engine) {}

    public function handle(SavedCalculation $saved): Calculation
    {
        return $this->engine->run(
            $saved->formula_id,
            StoredInputs::toRaw($saved->inputs),
            $saved->formula_version,
        );
    }

    /**
     * آیا اجرای دوباره همان خروجی ذخیره‌شده را می‌دهد؟
     *
     * برای صفحه تأیید اصالت گزارش (بخش ۱۶) و برای تست پذیرش همین بخش.
     *
     * مقایسه عمداً `===` روی آرایه نیست. عدد ۲۸٫۰ در JSON به‌صورت `28` نوشته
     * می‌شود و موقع خواندن **عدد صحیح** برمی‌گردد، در حالی که موتور همیشه
     * اعشاری می‌دهد؛ مقایسه سخت‌گیرانه آن را «بازتولیدنشده» می‌خواند و یک
     * گزارش کاملاً سالم را زیر سؤال می‌برد. پس مقدار عددی مقایسه می‌شود و
     * واحد به‌صورت دقیق.
     */
    public function matches(SavedCalculation $saved): bool
    {
        $replayed = $this->handle($saved)->toArray()['outputs'];

        if (! is_array($replayed) || array_keys($replayed) !== array_keys($saved->outputs)) {
            return false;
        }

        foreach ($replayed as $key => $output) {
            /** @var array{value: float, unit: string} $output */
            $stored = $saved->outputs[$key];

            if (! is_array($stored) || ($stored['unit'] ?? null) !== $output['unit']) {
                return false;
            }

            if (! $this->sameNumber((float) $stored['value'], $output['value'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * برابری عددی با رواداری نسبی بسیار کوچک.
     *
     * رواداری برای پوشاندن اختلاف واقعی نیست — اختلاف واقعی یعنی بازتولید
     * نشده — بلکه فقط برای رفت‌وبرگشت JSON است.
     */
    private function sameNumber(float $stored, float $replayed): bool
    {
        if ($stored === $replayed) {
            return true;
        }

        $scale = max(abs($stored), abs($replayed), 1.0);

        return abs($stored - $replayed) <= $scale * 1e-12;
    }
}
