<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Import\ImportAction;
use App\Modules\Chemicals\Domain\Import\ImportPlan;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Events\SubstancesImported;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * نوشتن یک گزارش تغییرات تأییدشده روی پایگاه داده.
 *
 * ورودی این اکشن `ImportPlan` است، نه فایل CSV خام: چیزی که مدیر در
 * پیش‌نمایش تأیید کرده، دقیقاً همان چیزی است که نوشته می‌شود. اگر بین
 * پیش‌نمایش و اجرا فایل دوباره خوانده می‌شد، می‌توانست چیز دیگری شده باشد.
 *
 * فایلی که همه ردیف‌هایش نامعتبر یا بدون‌تغییرند، هیچ نوشتنی ندارد و کل کار
 * در یک تراکنش انجام می‌شود — یا همه ردیف‌ها می‌روند یا هیچ‌کدام.
 *
 * ماده تازه همیشه **پیش‌نویس** می‌ماند. CSV فقط داده پایه ماده را می‌آورد
 * (نه حد مواجهه)، پس نمی‌تواند شرط انتشار را برآورده کند؛ حتی اگر می‌شد،
 * تصمیم انتشار سرمقاله‌ای است، نه پیامد یک فایل.
 */
final readonly class ApplyCsvImport
{
    public function __construct(
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /**
     * @throws RuntimeException اگر برنامه هیچ نوشتنی نداشته باشد
     */
    public function handle(ImportPlan $plan, ?int $actorId = null): void
    {
        if (! $plan->hasWritableChanges()) {
            throw new RuntimeException('این فایل هیچ ماده تازه یا تغییری ندارد؛ چیزی برای اجرا نیست.');
        }

        $updatedCas = [];

        $this->db->transaction(function () use ($plan, &$updatedCas): void {
            foreach ($plan->of(ImportAction::Create) as $row) {
                Substance::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'slug' => $this->uniqueSlug($row->data['name_en']),
                    'status' => SubstanceStatus::Draft,
                    ...$row->data,
                ]);
            }

            foreach ($plan->of(ImportAction::Update) as $row) {
                $substance = Substance::query()->where('cas_number', $row->data['cas_number'])->firstOrFail();

                $updates = [];

                foreach ($row->changes as $change) {
                    $updates[$change->field] = $change->after;
                }

                $substance->update($updates);
                $updatedCas[] = $substance->cas_number;
            }
        });

        $this->events->dispatch(new SubstancesImported(
            created: $plan->count(ImportAction::Create),
            updated: $plan->count(ImportAction::Update),
            updatedCasNumbers: $updatedCas,
            actorId: $actorId,
        ));
    }

    private function uniqueSlug(string $nameEn): string
    {
        $base = Str::slug($nameEn) ?: 'substance';
        $slug = $base;
        $suffix = 1;

        while (Substance::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
