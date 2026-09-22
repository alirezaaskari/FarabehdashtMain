<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Modules\Chemicals\Actions\ApplyCsvImport;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Import\ImportAction;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\CsvExporter;
use App\Modules\Chemicals\Services\CsvImporter;
use App\Modules\Core\Domain\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۰: «ورود CSV قبل از اجرا گزارش تغییرات می‌دهد.»
 */
final class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private const string HEADER = 'cas_number,name_fa,name_en,formula,molar_mass,physical_state';

    public function test_a_new_cas_number_is_planned_as_create_without_writing_anything(): void
    {
        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,C7H8,92.14,مایع فرّار");

        $this->assertSame(1, $plan->count(ImportAction::Create));
        $this->assertSame(0, Substance::query()->count(), 'برنامه‌ریزی نباید چیزی بنویسد.');
    }

    public function test_an_existing_cas_with_no_changed_field_is_unchanged(): void
    {
        $this->existing('108-88-3', 'تولوئن', 'Toluene');

        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,,");

        $this->assertSame(1, $plan->count(ImportAction::Unchanged));
        $this->assertSame(0, $plan->count(ImportAction::Update));
    }

    public function test_a_changed_field_is_reported_before_and_after(): void
    {
        $this->existing('108-88-3', 'تولوئن', 'Toluene', molarMass: 92.1);

        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن اصلاح‌شده,Toluene,,92.14,");

        $row = $plan->of(ImportAction::Update)[0];
        $fields = collect($row->changes)->keyBy('field');

        $this->assertSame('تولوئن', $fields['name_fa']->before);
        $this->assertSame('تولوئن اصلاح‌شده', $fields['name_fa']->after);
        $this->assertArrayNotHasKey('name_en', $fields->all(), 'ستون بدون تغییر نباید در گزارش بیاید.');
    }

    public function test_a_check_digit_failure_is_reported_as_invalid_not_silently_dropped(): void
    {
        $plan = $this->importer()->plan(self::HEADER."\n108-88-4,تولوئن,Toluene,,,");

        $this->assertSame(1, $plan->count(ImportAction::Invalid));
        $this->assertStringContainsString('رقم کنترلی', $plan->of(ImportAction::Invalid)[0]->reason);
    }

    public function test_a_duplicate_cas_within_the_same_file_is_invalid(): void
    {
        $plan = $this->importer()->plan(
            self::HEADER."\n108-88-3,تولوئن,Toluene,,,\n108-88-3,تولوئن دوباره,Toluene,,,",
        );

        $this->assertSame(1, $plan->count(ImportAction::Create));
        $this->assertSame(1, $plan->count(ImportAction::Invalid));
    }

    public function test_a_non_positive_molar_mass_is_invalid(): void
    {
        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,-5,");

        $this->assertSame(1, $plan->count(ImportAction::Invalid));
    }

    public function test_a_wrong_header_is_rejected_before_reading_any_row(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->importer()->plan("name,cas\nToluene,108-88-3");
    }

    public function test_a_file_over_the_row_cap_is_rejected(): void
    {
        $importer = new CsvImporter(['cas_number', 'name_fa', 'name_en', 'formula', 'molar_mass', 'physical_state'], maxRows: 1);

        $this->expectExceptionMessageMatches('/سقف مجاز/u');

        $importer->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,,\n71-43-2,بنزن,Benzene,,,");
    }

    public function test_apply_writes_exactly_the_plan_it_was_given(): void
    {
        $this->existing('71-43-2', 'بنزن', 'Benzene');

        $plan = $this->importer()->plan(
            self::HEADER."\n108-88-3,تولوئن,Toluene,,,\n71-43-2,بنزن اصلاح‌شده,Benzene,,,",
        );

        $this->app->make(ApplyCsvImport::class)->handle($plan);

        $this->assertDatabaseHas('substances', ['cas_number' => '108-88-3', 'name_fa' => 'تولوئن']);
        $this->assertDatabaseHas('substances', ['cas_number' => '71-43-2', 'name_fa' => 'بنزن اصلاح‌شده']);
    }

    public function test_a_newly_created_substance_from_csv_is_always_a_draft(): void
    {
        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,,");

        $this->app->make(ApplyCsvImport::class)->handle($plan);

        $substance = Substance::query()->where('cas_number', '108-88-3')->sole();

        // CSV فقط داده پایه می‌آورد، نه حد مواجهه؛ نمی‌تواند شرط انتشار را
        // برآورده کند، و حتی اگر می‌شد، انتشار تصمیم سرمقاله‌ای است.
        $this->assertSame(SubstanceStatus::Draft, $substance->status);
    }

    public function test_a_plan_with_nothing_writable_cannot_be_applied(): void
    {
        $this->existing('108-88-3', 'تولوئن', 'Toluene');

        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,,");

        $this->expectException(RuntimeException::class);

        $this->app->make(ApplyCsvImport::class)->handle($plan);
    }

    public function test_applying_a_plan_writes_one_audit_row(): void
    {
        $plan = $this->importer()->plan(self::HEADER."\n108-88-3,تولوئن,Toluene,,,");

        $this->app->make(ApplyCsvImport::class)->handle($plan, actorId: null);

        $row = AuditLog::query()->where('action', 'chemicals.substances_imported')->sole();

        $this->assertSame(1, $row->after['created'] ?? null);
    }

    public function test_exported_csv_can_be_imported_back_without_column_changes(): void
    {
        $this->existing('108-88-3', 'تولوئن', 'Toluene');

        $csv = $this->app->make(CsvExporter::class)->export(Substance::query()->get());
        $csv = ltrim($csv, "\u{FEFF}"); // BOM اکسل، نه بخشی از داده

        $plan = $this->importer()->plan($csv);

        $this->assertSame(1, $plan->count(ImportAction::Unchanged));
    }

    private function importer(): CsvImporter
    {
        return $this->app->make(CsvImporter::class);
    }

    private function existing(string $cas, string $nameFa, string $nameEn, ?float $molarMass = null): Substance
    {
        return Substance::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => Str::slug($nameEn),
            'cas_number' => $cas,
            'name_fa' => $nameFa,
            'name_en' => $nameEn,
            'molar_mass' => $molarMass,
            'status' => SubstanceStatus::Draft,
        ]);
    }
}
