<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Events\SubstanceSaved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ساخت یا ویرایش یک ماده از ویرایشگر پنل.
 *
 * مترادف‌ها، حدود مواجهه و نکته‌ها با هر ذخیره از نو نوشته می‌شوند: قید یکتای
 * (ماده، مرجع، نوع) و (ماده، نوع نکته، جای آن) جابه‌جایی ردیفی را ناممکن
 * می‌کند و این ردیف‌ها شناسه‌ای ندارند که جایی به آن اشاره شده باشد.
 *
 * وضعیت این‌جا عوض نمی‌شود؛ انتشار فقط از {@see PublishSubstance} می‌گذرد.
 * نشانی ماده منتشرشده ثابت می‌ماند.
 */
final readonly class SaveSubstance
{
    private const FIELDS = [
        'name_fa', 'name_en', 'formula', 'physical_state', 'description',
        'sampling_media', 'sampling_flow', 'analysis_method', 'method_number',
    ];

    /** نام فیلد فرم برای هر نوع نکته. */
    public const FACT_FIELDS = [
        'routes' => FactKind::Route,
        'symptoms' => FactKind::Symptom,
        'protection' => FactKind::Protection,
    ];

    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data  داده فرم پنل: فیلدهای ماده، `synonyms`، `limits` و
     *                                      فهرست‌های `routes`، `symptoms`، `protection`
     *
     * @throws InvalidArgumentException اگر شماره CAS رقم کنترلی نخواند
     */
    public function handle(?Substance $substance, array $data, ?int $actorId): Substance
    {
        $created = $substance === null;
        $cas = (string) CasNumber::fromString((string) self::text($data['cas_number'] ?? null));

        $saved = $this->db->transaction(function () use ($substance, $data, $cas): Substance {
            $substance ??= new Substance([
                'uuid' => (string) Str::uuid7(),
                'status' => SubstanceStatus::Draft,
            ]);

            foreach (self::FIELDS as $field) {
                $substance->{$field} = self::text($data[$field] ?? null);
            }

            $substance->molar_mass = is_numeric($data['molar_mass'] ?? null) ? (float) $data['molar_mass'] : null;
            $substance->cas_number = $cas;

            $slug = self::text($data['slug'] ?? null);

            if ($substance->status !== SubstanceStatus::Published && $slug !== null) {
                $substance->slug = Str::lower($slug);
            }

            $substance->save();

            $substance->synonyms()->delete();
            $substance->synonyms()->createMany($this->synonyms($data['synonyms'] ?? []));

            $substance->limits()->delete();
            $substance->limits()->createMany($this->limits($data['limits'] ?? []));

            $substance->facts()->delete();
            $substance->facts()->createMany($this->facts($data));

            return $substance->refresh()->load(['synonyms', 'limits', 'facts']);
        });

        $this->events->dispatch(new SubstanceSaved($saved, $actorId, $created));

        return $saved;
    }

    /** @return list<array{name: string}> */
    private function synonyms(mixed $names): array
    {
        $unique = [];

        foreach (is_array($names) ? $names : [] as $name) {
            $name = self::text($name);

            if ($name !== null) {
                $unique[$name] = ['name' => $name];
            }
        }

        return array_values($unique);
    }

    /** @return list<array<string, mixed>> */
    private function limits(mixed $limits): array
    {
        $rows = [];

        foreach (is_array($limits) ? $limits : [] as $limit) {
            if (! is_array($limit)) {
                continue;
            }

            $rows[] = [
                'authority' => $limit['authority'],
                'type' => $limit['type'],
                'value' => (float) $limit['value'],
                'unit' => (string) self::text($limit['unit'] ?? null),
                'note' => self::text($limit['note'] ?? null),
                'reference_title' => self::text($limit['reference_title'] ?? null),
                'reference_edition' => self::text($limit['reference_edition'] ?? null),
                'reference_year' => is_numeric($limit['reference_year'] ?? null) ? (int) $limit['reference_year'] : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{kind: FactKind, position: int, text: string}>
     */
    private function facts(array $data): array
    {
        $rows = [];

        foreach (self::FACT_FIELDS as $field => $kind) {
            $position = 0;

            foreach (is_array($data[$field] ?? null) ? $data[$field] : [] as $text) {
                $text = self::text($text);

                if ($text !== null) {
                    $rows[] = ['kind' => $kind, 'position' => ++$position, 'text' => $text];
                }
            }
        }

        return $rows;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
