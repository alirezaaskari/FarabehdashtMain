<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\AssessmentComparison;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\JalaliDate;
use App\Support\Measurement\MeasurementNumber;
use App\Support\PersianDigits;
use InvalidArgumentException;

/**
 * کنار هم گذاشتن ارزیابی‌های ذخیره‌شده یک روش پوسچر.
 *
 * در RULA، REBA و ROSA هر امتیاز، از عضو تا نهایی و سطح اقدام، هرچه
 * کمتر بهتر؛ پس جهت تغییر برای همه سطرها یکی است. پاسخ‌هایی که فرق
 * دارند جدا می‌آیند تا معلوم شود اصلاح دقیقاً چه چیزی را عوض کرده.
 */
final readonly class AssessmentComparer
{
    public const int MAX = 6;

    public function __construct(private ResultPresenter $presenter) {}

    /**
     * @param  list<SavedCalculation>  $assessments  به ترتیب ثبت، قدیمی‌تر اول
     */
    public function compare(ResolvedTool $tool, array $assessments): AssessmentComparison
    {
        $count = count($assessments);

        if ($count < 2 || $count > self::MAX) {
            throw new InvalidArgumentException(sprintf('برای مقایسه ۲ تا %s ارزیابی انتخاب کنید.', PersianDigits::from(self::MAX)));
        }

        if (! $tool->definition->isPostureAssessment()) {
            throw new InvalidArgumentException('مقایسه فقط برای روش‌های ارزیابی پوسچر است.');
        }

        foreach ($assessments as $assessment) {
            if ($assessment->tool_slug !== $tool->slug()) {
                throw new InvalidArgumentException('همه ارزیابی‌های مقایسه باید با یک روش انجام شده باشند.');
            }
        }

        $columns = [];

        foreach ($assessments as $index => $assessment) {
            $columns[] = [
                'uuid' => $assessment->uuid,
                'title' => $assessment->label ?? 'ارزیابی '.PersianDigits::from($index + 1),
                'date' => JalaliDate::short($assessment->created_at),
            ];
        }

        return new AssessmentComparison(
            method: $tool->definition->title,
            columns: $columns,
            scores: $this->scores($assessments),
            differences: $this->differences($tool, $assessments),
        );
    }

    /**
     * @param  list<SavedCalculation>  $assessments
     * @return list<array{label: string, values: list<string>, change: string|null, trend: string|null}>
     */
    private function scores(array $assessments): array
    {
        $rows = [];

        foreach (array_keys($assessments[0]->outputs) as $key) {
            $values = array_map(static fn (SavedCalculation $a): ?float => self::number($a->outputs[$key] ?? null), $assessments);

            [$change, $trend] = count($values) === 2 && $values[0] !== null && $values[1] !== null
                ? self::change($values[1] - $values[0])
                : [null, null];

            $rows[] = [
                'label' => $this->presenter->label((string) $key),
                'values' => array_map(static fn (?float $v): string => $v === null ? '—' : MeasurementNumber::format($v), $values),
                'change' => $change,
                'trend' => $trend,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<SavedCalculation>  $assessments
     * @return list<array{label: string, values: list<string>}>
     */
    private function differences(ResolvedTool $tool, array $assessments): array
    {
        $choices = $tool->definition->choiceLabels();
        $answers = [];
        $labels = [];

        foreach ($assessments as $index => $assessment) {
            foreach ($this->presenter->fromStored($assessment->inputs, $choices) as $row) {
                $answers[$row->key][$index] = $row->value;
                $labels[$row->key] = $row->label;
            }
        }

        $rows = [];

        foreach ($answers as $key => $values) {
            $values = array_map(static fn (int $i): string => $values[$i] ?? '—', array_keys($assessments));

            if (count(array_unique($values)) > 1) {
                $rows[] = ['label' => $labels[$key], 'values' => $values];
            }
        }

        return $rows;
    }

    private static function number(mixed $stored): ?float
    {
        return is_array($stored) && is_numeric($stored['value'] ?? null) ? (float) $stored['value'] : null;
    }

    /**
     * @return array{string, string}
     */
    private static function change(float $delta): array
    {
        if (abs($delta) < 1e-9) {
            return ['0', 'same'];
        }

        return [($delta > 0 ? '+' : '−').MeasurementNumber::format(abs($delta)), $delta < 0 ? 'better' : 'worse'];
    }
}
