<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Contracts\RevisionHistory;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Measurement\MeasurementNumber;
use DateTimeInterface;

/**
 * تاریخچه تغییرات یک ماده به زبان خواننده (بخش ۱۸-۱۱).
 *
 * هر نسخه با نسخه پیش از خودش مقایسه می‌شود و فقط آنچه عوض شده می‌آید:
 * نام، CAS، فرمول، جرم مولکولی، وضعیت و حدهای مواجهه (به کلید مرجع + نوع).
 * نویسنده نمی‌آید؛ صفحه عمومی است و شناسه کاربر داده شخصی است.
 */
final readonly class SubstanceHistory
{
    private const FIELDS = [
        'name_fa' => 'نام فارسی',
        'name_en' => 'نام انگلیسی',
        'cas_number' => 'شماره CAS',
        'formula' => 'فرمول',
        'molar_mass' => 'جرم مولکولی',
        'status' => 'وضعیت',
    ];

    /** مقدار فارسی راست‌چین می‌ماند؛ بقیه عدد و شناسه لاتین‌اند. */
    private const PERSIAN = ['name_fa', 'status'];

    public function __construct(private RevisionHistory $revisions) {}

    /**
     * @return list<array{version: int, at: DateTimeInterface, reason: ?string, first: bool,
     *     changes: list<array{label: string, before: ?string, after: ?string, ltr: bool}>}>
     */
    public function for(Substance $substance): array
    {
        $records = array_reverse($this->revisions->of(Substance::class, $substance->id));
        $entries = [];
        $previous = null;

        foreach ($records as $record) {
            $changes = $previous === null ? [] : $this->diff($previous, $record->snapshot);

            // ذخیره‌ای که فقط توضیح یا مترادف را عوض کرده در نسخه‌ها هست ولی به کار این صفحه نمی‌آید.
            if ($previous === null || $changes !== []) {
                $entries[] = [
                    'version' => $record->version,
                    'at' => $record->recordedAt,
                    'reason' => $record->reason,
                    'first' => $previous === null,
                    'changes' => $changes,
                ];
            }

            $previous = $record->snapshot;
        }

        return array_reverse($entries);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<array{label: string, before: ?string, after: ?string, ltr: bool}>
     */
    private function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (self::FIELDS as $key => $label) {
            $old = $this->field($key, $before[$key] ?? null);
            $new = $this->field($key, $after[$key] ?? null);

            if ($old !== $new) {
                $changes[] = ['label' => $label, 'before' => $old, 'after' => $new, 'ltr' => ! in_array($key, self::PERSIAN, true)];
            }
        }

        $oldLimits = $this->limits($before['limits'] ?? []);
        $newLimits = $this->limits($after['limits'] ?? []);

        foreach (array_unique([...array_keys($oldLimits), ...array_keys($newLimits)]) as $key) {
            $old = $oldLimits[$key]['text'] ?? null;
            $new = $newLimits[$key]['text'] ?? null;

            if ($old !== $new) {
                $changes[] = ['label' => ($oldLimits[$key] ?? $newLimits[$key])['label'], 'before' => $old, 'after' => $new, 'ltr' => true];
            }
        }

        return $changes;
    }

    private function field(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($key === 'status') {
            return SubstanceStatus::tryFrom((string) $value)?->label() ?? (string) $value;
        }

        if (is_int($value) || is_float($value)) {
            return MeasurementNumber::format((float) $value);
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @return array<string, array{label: string, text: string}>
     */
    private function limits(mixed $limits): array
    {
        $keyed = [];

        foreach (is_array($limits) ? $limits : [] as $limit) {
            if (! is_array($limit) || ! isset($limit['authority'], $limit['type'])) {
                continue;
            }

            $authority = LimitAuthority::tryFrom((string) $limit['authority'])?->label() ?? (string) $limit['authority'];
            $type = LimitType::tryFrom((string) $limit['type'])?->shortLabel() ?? (string) $limit['type'];

            $keyed[$limit['authority'].'.'.$limit['type']] = [
                'label' => 'حد '.$type.' — '.$authority,
                'text' => trim($this->field('value', $limit['value'] ?? null).' '.($limit['unit'] ?? '')),
            ];
        }

        return $keyed;
    }
}
