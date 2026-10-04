<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Actions\SyncBundledSubstances;
use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\BundledSubstances;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * داده اولیه بانک مواد: هر ردیف باید بی‌دست‌کاری منتشرشدنی باشد و
 * همگام‌سازی هرگز ویرایش مدیر را برنگرداند.
 */
final class BundledSubstancesTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array<string, mixed>> */
    private function entries(): array
    {
        return $this->app->make(BundledSubstances::class)->all();
    }

    public function test_every_bundled_entry_is_publishable_and_sourced(): void
    {
        $entries = $this->entries();
        $this->assertGreaterThanOrEqual(300, count($entries));

        $cas = array_column($entries, 'cas_number');
        $slugs = array_column($entries, 'slug');
        $this->assertSame($cas, array_unique($cas), 'شماره CAS تکراری است.');
        $this->assertSame($slugs, array_unique($slugs), 'نشانی تکراری است.');

        foreach ($entries as $entry) {
            $label = $entry['cas_number'];

            $this->assertTrue(CasNumber::isValid($entry['cas_number']), "{$label}: رقم کنترلی CAS نمی‌خواند.");
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $entry['slug'], $label);
            $this->assertNotSame('', trim($entry['name_fa']), $label);
            $this->assertLessThanOrEqual(64, mb_strlen((string) $entry['formula']), $label);
            $this->assertLessThanOrEqual(64, mb_strlen((string) $entry['method_number']), $label);
            $this->assertStringContainsString('https://', (string) $entry['sources'], "{$label}: منبع مشخصات ندارد.");

            foreach (['routes', 'symptoms', 'protection', 'synonyms'] as $list) {
                foreach ($entry[$list] as $text) {
                    $this->assertLessThanOrEqual(255, mb_strlen($text), "{$label}: متن «{$list}» از ستون بلندتر است.");
                }
            }

            $this->assertNotEmpty($entry['limits'], "{$label}: حد مواجهه ندارد.");
            $pairs = [];

            foreach ($entry['limits'] as $limit) {
                $this->assertNotNull(LimitAuthority::tryFrom($limit['authority']), $label);
                $this->assertNotNull(LimitType::tryFrom($limit['type']), $label);
                $this->assertGreaterThan(0, $limit['value'], $label);
                $this->assertNotSame('', $limit['unit'], $label);
                $this->assertNotSame('', $limit['reference_title'], $label);
                $this->assertTrue($limit['reference_edition'] !== null || $limit['reference_year'] !== null, "{$label}: منبع حد نسخه ندارد.");
                $this->assertStringStartsWith('https://', (string) $limit['reference_url'], "{$label}: حد پیوند منبع ندارد.");
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $limit['reference_accessed_on'], $label);
                $pairs[] = $limit['authority'].'/'.$limit['type'];
            }

            $this->assertSame($pairs, array_unique($pairs), "{$label}: دو حد با یک مرجع و یک نوع.");
        }
    }

    public function test_sync_publishes_every_entry_once(): void
    {
        $first = $this->app->make(SyncBundledSubstances::class)->handle();

        $this->assertSame([], $first->failed);
        $this->assertCount(count($this->entries()), $first->created);
        $this->assertSame(count($first->created), Substance::query()->where('status', SubstanceStatus::Published->value)->count());

        $second = $this->app->make(SyncBundledSubstances::class)->handle();

        $this->assertSame([], $second->created);
        $this->assertCount(count($first->created), $second->skipped);
    }

    public function test_sync_never_overwrites_a_substance_the_admin_already_has(): void
    {
        $this->app->make(SaveSubstance::class)->handle(null, [
            'cas_number' => '108-88-3',
            'slug' => 'toluene',
            'name_fa' => 'تولوئن',
            'name_en' => 'Toluene',
            'description' => 'متن مدیر',
        ], null);

        $result = $this->app->make(SyncBundledSubstances::class)->handle();

        $this->assertContains('108-88-3', $result->skipped);
        $toluene = Substance::query()->where('cas_number', '108-88-3')->sole();
        $this->assertSame('متن مدیر', $toluene->description);
        $this->assertSame(SubstanceStatus::Draft, $toluene->status);
    }

    public function test_a_slug_taken_by_another_substance_gets_a_free_one(): void
    {
        $this->app->make(SaveSubstance::class)->handle(null, [
            'cas_number' => '7732-18-5',
            'slug' => 'toluene',
            'name_fa' => 'آب',
            'name_en' => 'Water',
        ], null);

        $this->app->make(SyncBundledSubstances::class)->handle();

        $this->assertSame('toluene-2', Substance::query()->where('cas_number', '108-88-3')->sole()->slug);
    }

    public function test_substance_page_shows_limit_notes_and_sources(): void
    {
        $this->artisan('fbh:sync-chemicals')->assertSuccessful();

        $this->get(route('chemicals.show', 'toluene'))
            ->assertOk()
            ->assertSee('معادل 375 mg/m³')
            ->assertSee('منابع این صفحه')
            ->assertSee('https://www.cdc.gov/niosh/npg/npgd0619.html', false)
            ->assertSee('NIOSH 1501')
            ->assertSee('دیده‌شده در')
            ->assertSee(route('chemicals.sources'), false);
    }

    public function test_sources_page_explains_the_authorities_with_live_counts(): void
    {
        $this->artisan('fbh:sync-chemicals')->assertSuccessful();

        $this->get(route('chemicals.sources'))
            ->assertOk()
            ->assertSee('منابع و روش کار بانک مواد')
            ->assertSee('data-page-help="chemicals-sources"', false)
            ->assertSee('NIOSH REL')
            ->assertSee('https://www.cdc.gov/niosh/npg/', false);
    }

    public function test_a_non_https_reference_link_is_dropped(): void
    {
        $substance = $this->app->make(SaveSubstance::class)->handle(null, [
            'cas_number' => '108-88-3',
            'slug' => 'toluene',
            'name_fa' => 'تولوئن',
            'name_en' => 'Toluene',
            'limits' => [[
                'authority' => 'niosh', 'type' => 'twa', 'value' => 100, 'unit' => 'ppm',
                'reference_title' => 'NIOSH', 'reference_year' => 2026,
                'reference_url' => 'javascript:alert(1)',
            ]],
        ], null);

        $this->assertNull($substance->limits->sole()->reference_url);
    }
}
