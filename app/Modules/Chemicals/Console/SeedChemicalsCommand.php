<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Console;

use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceFact;
use App\Modules\Chemicals\Domain\SubstanceSynonym;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * چند ماده نمونه، برای محیط توسعه و بازبینی چشمی.
 *
 * فعلاً بانک داده اولیه واقعی ندارد — تصمیم مدیر: «فعلاً فقط چند ماده نمونه
 * دستی.» این دستور همان چند ماده را می‌سازد؛ ساختار CSV مستقل از این تصمیم
 * است و هر وقت منبع داده واقعی مشخص شد، همین ستون‌ها را می‌پذیرد.
 *
 * در production کار نمی‌کند — مثل fbh:seed-encyclopedia.
 */
final class SeedChemicalsCommand extends Command
{
    protected $signature = 'fbh:seed-chemicals';

    protected $description = 'ساخت چند ماده نمونه بانک مواد شیمیایی — فقط محیط توسعه';

    public function handle(PublishSubstance $publish): int
    {
        if (app()->environment('production')) {
            $this->error('محتوای نمونه در محیط اصلی ساخته نمی‌شود.');

            return self::FAILURE;
        }

        $created = 0;

        foreach ($this->content() as $draft) {
            if (Substance::query()->where('cas_number', $draft['cas'])->exists()) {
                continue;
            }

            $substance = Substance::query()->create([
                'uuid' => (string) Str::uuid7(),
                'slug' => Str::slug($draft['name_en']),
                'cas_number' => $draft['cas'],
                'name_fa' => $draft['name_fa'],
                'name_en' => $draft['name_en'],
                'formula' => $draft['formula'],
                'molar_mass' => $draft['molar_mass'],
                'physical_state' => $draft['physical_state'],
                'description' => $draft['description'],
                'sampling_media' => $draft['sampling_media'] ?? null,
                'analysis_method' => $draft['analysis_method'] ?? null,
                'status' => SubstanceStatus::Draft,
            ]);

            foreach ($draft['synonyms'] as $synonym) {
                SubstanceSynonym::query()->create(['substance_id' => $substance->id, 'name' => $synonym]);
            }

            foreach ($draft['facts'] as $kind => $items) {
                foreach ($items as $position => $text) {
                    SubstanceFact::query()->create([
                        'substance_id' => $substance->id,
                        'kind' => $kind,
                        'position' => $position + 1,
                        'text' => $text,
                    ]);
                }
            }

            foreach ($draft['limits'] as $limit) {
                $substance->limits()->create($limit);
            }

            try {
                $publish->handle($substance->refresh());
                $created++;
            } catch (RuntimeException $exception) {
                $this->warn($draft['cas'].': '.$exception->getMessage());
            }
        }

        $this->info(sprintf('%d ماده نمونه ساخته شد.', $created));

        return self::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function content(): array
    {
        return [
            [
                'cas' => '108-88-3',
                'name_fa' => 'تولوئن',
                'name_en' => 'Toluene',
                'formula' => 'C₇H₈',
                'molar_mass' => 92.14,
                'physical_state' => 'مایع فرّار',
                'description' => 'حلال آروماتیک مایع و بی‌رنگ با بوی مشخص، پرکاربرد در صنایع رنگ، چسب، چاپ و پتروشیمی. مواجهه شغلی عمدتاً از راه استنشاق بخار و تماس پوستی رخ می‌دهد.',
                'sampling_media' => 'لوله زغال فعال',
                'analysis_method' => 'GC-FID',
                'synonyms' => ['متیل‌بنزن', 'فنیل‌متان'],
                'facts' => [
                    FactKind::Route->value => ['استنشاق بخار — مسیر اصلی', 'جذب پوستی', 'تماس چشمی'],
                    FactKind::Symptom->value => ['سردرد، سرگیجه و خواب‌آلودگی', 'تحریک چشم و مجاری تنفسی فوقانی', 'اثرات عصبی در مواجهه مزمن'],
                    FactKind::Protection->value => ['ماسک با فیلتر بخار آلی مناسب', 'دستکش مقاوم به حلال آروماتیک', 'عینک ایمنی با حفاظ جانبی'],
                ],
                'limits' => [
                    ['authority' => LimitAuthority::IranOel->value, 'type' => LimitType::Twa->value, 'value' => 50, 'unit' => 'ppm', 'reference_title' => 'حدود مجاز مواجهه شغلی ایران', 'reference_year' => 1400],
                    ['authority' => LimitAuthority::Acgih->value, 'type' => LimitType::Twa->value, 'value' => 20, 'unit' => 'ppm', 'note' => 'با نشان پوستی', 'reference_title' => 'ACGIH TLVs and BEIs', 'reference_year' => 2023],
                    ['authority' => LimitAuthority::Niosh->value, 'type' => LimitType::Twa->value, 'value' => 100, 'unit' => 'ppm', 'reference_title' => 'NIOSH Pocket Guide to Chemical Hazards', 'reference_year' => 2007],
                    ['authority' => LimitAuthority::Niosh->value, 'type' => LimitType::Stel->value, 'value' => 150, 'unit' => 'ppm', 'reference_title' => 'NIOSH Pocket Guide to Chemical Hazards', 'reference_year' => 2007],
                ],
            ],
            [
                'cas' => '71-43-2',
                'name_fa' => 'بنزن',
                'name_en' => 'Benzene',
                'formula' => 'C₆H₆',
                'molar_mass' => 78.11,
                'physical_state' => 'مایع فرّار',
                'description' => 'حلال آروماتیک با سرطان‌زایی شناخته‌شده در انسان؛ در پالایش نفت، پتروشیمی و به‌صورت ناخالصی در بنزین یافت می‌شود.',
                'sampling_media' => 'لوله زغال فعال',
                'analysis_method' => 'GC-FID',
                'synonyms' => [],
                'facts' => [
                    FactKind::Route->value => ['استنشاق بخار — مسیر اصلی', 'جذب پوستی محدود'],
                    FactKind::Symptom->value => ['سرکوب مغز استخوان در مواجهه مزمن', 'افزایش خطر لوسمی', 'سردرد و سرگیجه در مواجهه حاد'],
                    FactKind::Protection->value => ['فیلتر بخار آلی — کفایت آن را با غلظت محیط بسنجید', 'کنترل مهندسی بر حفاظت فردی مقدم است'],
                ],
                'limits' => [
                    ['authority' => LimitAuthority::IranOel->value, 'type' => LimitType::Twa->value, 'value' => 1, 'unit' => 'ppm', 'reference_title' => 'حدود مجاز مواجهه شغلی ایران', 'reference_year' => 1400],
                    ['authority' => LimitAuthority::Acgih->value, 'type' => LimitType::Twa->value, 'value' => 0.5, 'unit' => 'ppm', 'reference_title' => 'ACGIH TLVs and BEIs', 'reference_year' => 2023],
                    ['authority' => LimitAuthority::Osha->value, 'type' => LimitType::Twa->value, 'value' => 1, 'unit' => 'ppm', 'reference_title' => 'OSHA 29 CFR 1910.1028', 'reference_year' => 2019],
                ],
            ],
            [
                'cas' => '50-00-0',
                'name_fa' => 'فرمالدئید',
                'name_en' => 'Formaldehyde',
                'formula' => 'CH₂O',
                'molar_mass' => 30.03,
                'physical_state' => 'گاز محرک',
                'description' => 'گاز محرک با بوی تند، در تولید رزین، چسب چوب و به‌عنوان ماده نگه‌دارنده در آزمایشگاه‌های پاتولوژی به‌کار می‌رود.',
                'sampling_media' => 'شیشه‌واره حاوی محلول جاذب',
                'analysis_method' => 'HPLC',
                'synonyms' => ['متانال'],
                'facts' => [
                    FactKind::Route->value => ['استنشاق — مسیر اصلی', 'تماس چشمی و مخاطی'],
                    FactKind::Symptom->value => ['تحریک شدید چشم و مجاری تنفسی', 'حساسیت پوستی', 'رده‌بندی سرطان‌زا در مواجهه مزمن'],
                    FactKind::Protection->value => ['ماسک با فیلتر مخصوص فرمالدئید', 'عینک بسته و دستکش نیتریل', 'تهویه موضعی در محل کار با محلول'],
                ],
                'limits' => [
                    ['authority' => LimitAuthority::IranOel->value, 'type' => LimitType::Ceiling->value, 'value' => 0.3, 'unit' => 'ppm', 'reference_title' => 'حدود مجاز مواجهه شغلی ایران', 'reference_year' => 1400],
                    ['authority' => LimitAuthority::Acgih->value, 'type' => LimitType::Ceiling->value, 'value' => 0.3, 'unit' => 'ppm', 'reference_title' => 'ACGIH TLVs and BEIs', 'reference_year' => 2023],
                ],
            ],
        ];
    }
}
