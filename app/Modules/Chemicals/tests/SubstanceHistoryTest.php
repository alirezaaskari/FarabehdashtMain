<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Domain\Substance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** صفحه تاریخچه تغییرات ماده (بخش ۱۸-۱۱). */
final class SubstanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_history_shows_what_changed_and_why(): void
    {
        $substance = $this->save(null, []);
        $substance = $this->app->make(PublishSubstance::class)->handle($substance, null);
        $this->save($substance, ['description' => 'فقط متن عوض شد']);
        $this->save($substance, [
            'molar_mass' => 92.5,
            'limits' => [$this->limit('acgih', 'twa', 50), $this->limit('niosh', 'stel', 150)],
        ]);

        $html = $this->get(route('chemicals.history', 'toluene'))
            ->assertOk()
            ->assertSee('data-page-help="chemicals-history"', false)
            ->assertSee('ماده با همین داده‌ها به بانک اضافه شد.')
            ->assertSee('انتشار')
            ->assertSee('پیش‌نویس')
            ->assertSee('حد TWA — ACGIH')
            ->assertSee('حد STEL — NIOSH')
            ->assertSee('(افزوده شد)')
            ->assertDontSee('نسخه ۳')
            ->getContent();

        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('#<del[^>]*>.*?92\.14.*?</del>#s', $html);
        $this->assertStringContainsString('>50 ppm<', $html);
    }

    public function test_the_substance_page_links_to_its_history(): void
    {
        $this->app->make(PublishSubstance::class)->handle($this->save(null, []), null);

        $this->get(route('chemicals.show', 'toluene'))
            ->assertOk()
            ->assertSee(route('chemicals.history', 'toluene'), false);
    }

    public function test_a_draft_has_no_public_history(): void
    {
        $this->save(null, []);

        $this->get(route('chemicals.history', 'toluene'))->assertNotFound();
    }

    /** @param  array<string, mixed>  $overrides */
    private function save(?Substance $substance, array $overrides): Substance
    {
        return $this->app->make(SaveSubstance::class)->handle($substance, array_replace([
            'name_fa' => 'تولوئن',
            'name_en' => 'Toluene',
            'cas_number' => '108-88-3',
            'slug' => 'toluene',
            'synonyms' => [],
            'formula' => 'C7H8',
            'molar_mass' => 92.14,
            'physical_state' => 'مایع',
            'description' => null,
            'limits' => [$this->limit('acgih', 'twa', 20)],
            'routes' => ['استنشاق'],
            'symptoms' => ['سردرد'],
            'protection' => [],
            'sampling_media' => null,
            'sampling_flow' => null,
            'analysis_method' => null,
            'method_number' => null,
        ], $overrides), null);
    }

    /** @return array<string, mixed> */
    private function limit(string $authority, string $type, int $value): array
    {
        return [
            'authority' => $authority,
            'type' => $type,
            'value' => $value,
            'unit' => 'ppm',
            'note' => null,
            'reference_title' => 'Reference',
            'reference_edition' => null,
            'reference_year' => 2024,
        ];
    }
}
