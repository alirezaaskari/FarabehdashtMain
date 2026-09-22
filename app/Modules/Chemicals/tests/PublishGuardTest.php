<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Core\Domain\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۰: «حد مواجهه بدون منبع منتشر نمی‌شود.»
 */
final class PublishGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_substance_with_no_exposure_limit_is_not_published(): void
    {
        $substance = $this->substance();

        try {
            $this->publish($substance);
            $this->fail('ماده بدون هیچ حد مواجهه‌ای نباید منتشر شود.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('هیچ حد مواجهه', $exception->getMessage());
        }

        $this->assertSame(SubstanceStatus::Draft, $substance->refresh()->status);
    }

    public function test_a_limit_without_a_reference_title_blocks_publication(): void
    {
        $substance = $this->substance();
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
        ]);

        $this->expectExceptionMessageMatches('/منبع نسخه‌دار/u');

        $this->publish($substance->refresh());
    }

    public function test_a_reference_title_without_edition_or_year_does_not_count(): void
    {
        $substance = $this->substance();
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
            'reference_title' => 'ACGIH TLVs and BEIs',
            // نه ویرایش، نه سال — بدون این‌ها به هر نسخه‌ای اشاره می‌کند.
        ]);

        $this->expectException(RuntimeException::class);

        $this->publish($substance->refresh());
    }

    public function test_one_unsourced_limit_blocks_the_whole_substance(): void
    {
        $substance = $this->substance();
        $substance->limits()->create([
            'authority' => LimitAuthority::IranOel->value,
            'type' => LimitType::Twa->value,
            'value' => 50,
            'unit' => 'ppm',
            'reference_title' => 'حدود مجاز مواجهه شغلی ایران',
            'reference_year' => 1400,
        ]);
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
            // این یکی منبع ندارد.
        ]);

        $this->expectExceptionMessageMatches('/ACGIH/u');

        $this->publish($substance->refresh());
    }

    public function test_an_invalid_cas_number_blocks_publication_even_with_sourced_limits(): void
    {
        $substance = $this->substance(cas: '108-88-4'); // رقم کنترلی اشتباه
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
            'reference_title' => 'ACGIH TLVs and BEIs',
            'reference_year' => 2023,
        ]);

        $this->expectException(RuntimeException::class);

        $this->publish($substance->refresh());
    }

    public function test_a_fully_sourced_substance_is_published(): void
    {
        $substance = $this->substance();
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
            'reference_title' => 'ACGIH TLVs and BEIs',
            'reference_year' => 2023,
        ]);

        $published = $this->publish($substance->refresh());

        $this->assertSame(SubstanceStatus::Published, $published->status);
        $this->assertNotNull($published->reviewed_at);
    }

    public function test_publishing_writes_one_audit_row_with_the_reference_line(): void
    {
        $substance = $this->substance();
        $substance->limits()->create([
            'authority' => LimitAuthority::Acgih->value,
            'type' => LimitType::Twa->value,
            'value' => 20,
            'unit' => 'ppm',
            'reference_title' => 'ACGIH TLVs and BEIs',
            'reference_year' => 2023,
        ]);

        $this->publish($substance->refresh());

        $row = AuditLog::query()->where('action', 'chemicals.substance_published')->sole();

        $this->assertSame($substance->uuid, $row->subject_id);
        $this->assertStringContainsString('2023', json_encode($row->after, JSON_THROW_ON_ERROR));
    }

    private function publish(Substance $substance): Substance
    {
        return $this->app->make(PublishSubstance::class)->handle($substance);
    }

    private function substance(string $cas = '108-88-3'): Substance
    {
        return Substance::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'test-'.Str::random(8),
            'cas_number' => $cas,
            'name_fa' => 'ماده آزمایشی',
            'name_en' => 'Test Substance',
            'status' => SubstanceStatus::Draft,
        ])->refresh();
    }
}
