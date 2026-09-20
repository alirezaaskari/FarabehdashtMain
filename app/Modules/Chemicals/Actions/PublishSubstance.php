<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\ExposureLimit;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Events\SubstancePublished;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

/**
 * انتشار یک ماده.
 *
 * معیار پذیرش این بخش: **حد مواجهه بدون منبع منتشر نمی‌شود.**
 *
 * چرا قاعده این‌جاست و نه در ستون NOT NULL: پیش‌نویسی که کارشناس دارد
 * پرش می‌کند باید بتواند ذخیره شود؛ شرط، وضعیتِ ماده است نه وجود ردیف. به‌علاوه
 * ورود CSV ردیف ناقص می‌آورد و باید بتواند ذخیره‌اش کند تا مدیر ببیند چه چیزی
 * کم است.
 *
 * سه شرط:
 *
 * ۱. **هر** حد مواجهه منبع نسخه‌دار داشته باشد. یکی هم بدون منبع باشد، کل ماده
 *    منتشر نمی‌شود — چون کاربر جدول را یکجا می‌خواند و نمی‌داند کدام ردیف
 *    پشتوانه دارد.
 * ۲. دست‌کم یک حد مواجهه داشته باشد. صفحه‌ای که فقط اسم ماده را بگوید، ارزش
 *    استناد ندارد.
 * ۳. شماره CAS با رقم کنترلی‌اش بخواند.
 */
final readonly class PublishSubstance
{
    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * @throws RuntimeException اگر ماده آماده انتشار نباشد
     */
    public function handle(Substance $substance, ?int $actorId = null, ?Carbon $now = null): Substance
    {
        $this->guard($substance);

        $now ??= Carbon::now();

        $published = $this->db->transaction(function () use ($substance, $now): Substance {
            $substance->forceFill([
                'status' => SubstanceStatus::Published,
                'reviewed_at' => $now,
            ])->save();

            return $substance->refresh();
        });

        $this->events->dispatch(new SubstancePublished($published, $actorId));

        return $published;
    }

    /**
     * @throws RuntimeException
     */
    private function guard(Substance $substance): void
    {
        $limits = $substance->limits()->get();

        if ($limits->isEmpty()) {
            throw new RuntimeException(
                'این ماده هیچ حد مواجهه‌ای ندارد و منتشر نمی‌شود؛ صفحه بدون حد، ارزش استناد ندارد.',
            );
        }

        $unsourced = $limits
            ->reject(static fn (ExposureLimit $limit): bool => $limit->hasVersionedReference())
            ->map(static fn (ExposureLimit $limit): string => sprintf(
                '%s %s',
                $limit->authority->label(),
                $limit->type->shortLabel(),
            ))
            ->values()
            ->all();

        if ($unsourced !== []) {
            throw new RuntimeException(sprintf(
                'این حدود مواجهه منبع نسخه‌دار ندارند و ماده منتشر نمی‌شود: %s',
                implode('، ', $unsourced),
            ));
        }

        try {
            CasNumber::fromString($substance->cas_number);
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
    }
}
