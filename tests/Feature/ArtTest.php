<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * تصویرهای خطی (x-art): هر حالت برای آقا و خانم هست، رنگ فقط از توکن‌ها
 * می‌آید و نام ناشناخته صفحه را نمی‌شکند.
 */
final class ArtTest extends TestCase
{
    use RefreshDatabase;

    private const ART = __DIR__.'/../../resources/views/components/art';

    public function test_every_pose_exists_for_both_a_man_and_a_woman(): void
    {
        $poses = array_unique(array_map(
            static fn (string $file): string => (string) preg_replace('/-[mf]\.blade\.php$/', '', basename($file)),
            glob(self::ART.'/character/*.blade.php') ?: [],
        ));

        $this->assertGreaterThanOrEqual(20, count($poses));

        foreach ($poses as $pose) {
            foreach (['m', 'f'] as $gender) {
                $this->assertFileExists(self::ART."/character/{$pose}-{$gender}.blade.php");
            }
        }
    }

    public function test_the_drawings_use_theme_tokens_and_never_a_hex_colour(): void
    {
        foreach (glob(self::ART.'/{character,scene}/*.blade.php', GLOB_BRACE) ?: [] as $file) {
            $svg = (string) file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $svg, basename($file));
            $this->assertStringContainsString('currentColor', $svg, basename($file));
        }
    }

    public function test_a_character_renders_as_decorative_inline_svg(): void
    {
        $html = Blade::render('<x-art name="character.measure" class="h-24" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('h-24', $html);
    }

    public function test_the_gender_can_be_chosen_and_an_unknown_name_draws_nothing(): void
    {
        $this->assertNotSame(
            Blade::render('<x-art name="character.measure" gender="m" />'),
            Blade::render('<x-art name="character.measure" gender="f" />'),
        );

        $this->assertSame('', trim(Blade::render('<x-art name="character.nope" />')));
        $this->assertSame('', trim(Blade::render('<x-art name="nope" />')));
    }

    public function test_the_homepage_and_the_404_page_carry_the_drawings(): void
    {
        $this->get('/')->assertOk()->assertSee('aria-hidden="true" focusable="false"', false);
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('viewBox="0 0 200 260"', false);
    }
}
