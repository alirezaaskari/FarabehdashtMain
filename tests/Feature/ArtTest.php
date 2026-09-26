<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * تصویرهای نقاشی‌گونه (x-art): هر تصویر فقط یک جای سایت می‌نشیند، رنگش فقط
 * از توکن‌ها می‌آید و نام ناشناخته صفحه را نمی‌شکند.
 */
final class ArtTest extends TestCase
{
    use RefreshDatabase;

    private const PICTURES = __DIR__.'/../../resources/views/components/art/pictures';

    /**
     * @return array<string, string> نام تصویر ← محتوای SVG
     */
    private function pictures(): array
    {
        $pictures = [];

        foreach (glob(self::PICTURES.'/*.blade.php') ?: [] as $file) {
            $pictures[basename($file, '.blade.php')] = (string) file_get_contents($file);
        }

        return $pictures;
    }

    /**
     * @return array<string, string> مسیر ← محتوای همه قالب‌ها و کلاس‌هایی که تصویر را صدا می‌زنند
     */
    private function callers(): array
    {
        $finder = Finder::create()->files()->name(['*.php'])
            ->in([base_path('app'), resource_path('views')])
            ->exclude(['tests'])
            ->notPath('components/art');

        $sources = [];

        foreach ($finder as $file) {
            $sources[$file->getRelativePathname()] = $file->getContents();
        }

        return $sources;
    }

    public function test_the_site_has_a_large_set_of_pictures(): void
    {
        $this->assertGreaterThanOrEqual(100, count($this->pictures()));
    }

    public function test_every_picture_is_used_exactly_once_so_none_repeats(): void
    {
        $callers = implode("\n", $this->callers());

        foreach (array_keys($this->pictures()) as $name) {
            // art="…" و name="…" در قالب و art: '…' در بخش صفحه اصلی؛ فهرست‌های صفحه
            // اصلی (کارت‌ها، قدم‌ها، نقش‌ها) نام را اولین عضو آرایه می‌گذارند.
            $quoted = preg_quote($name, '/');
            $list = str_starts_with($name, 'home-') ? '|\[\''.$quoted.'\',' : '';
            $uses = preg_match_all('/(?:\b(?:art|name)="'.$quoted.'"|\bart: \''.$quoted.'\''.$list.')/', $callers);

            $this->assertSame(1, $uses, "تصویر {$name} باید دقیقاً یک جا به کار رود، {$uses} جا به کار رفته است.");
        }
    }

    public function test_every_named_picture_exists(): void
    {
        $pictures = $this->pictures();

        foreach ($this->callers() as $path => $source) {
            preg_match_all('/<x-(?:art|page-header|empty-state|errors\.layout|layouts\.workspace)\b[^>]*?\s(?:name|art)="([^"$]+)"/', $source, $matches);

            foreach ($matches[1] as $name) {
                $this->assertArrayHasKey($name, $pictures, "{$path} تصویر ناموجود {$name} را صدا می‌زند.");
            }
        }
    }

    public function test_the_drawings_use_theme_tokens_and_never_a_hex_colour(): void
    {
        $tokens = (string) file_get_contents(resource_path('css/tokens.css'));

        foreach ($this->pictures() as $name => $svg) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $svg, $name);
            $this->assertStringContainsString('currentColor', $svg, $name);

            preg_match_all('/var\((--fbh-[a-z0-9-]+)\)/', $svg, $vars);

            foreach (array_unique($vars[1]) as $var) {
                $this->assertStringContainsString($var.':', $tokens, "{$name} از توکن تعریف‌نشده {$var} استفاده می‌کند.");
            }
        }
    }

    public function test_each_picture_keeps_its_filter_ids_to_itself(): void
    {
        foreach ($this->pictures() as $name => $svg) {
            preg_match_all('/\sid="([^"]+)"/', $svg, $ids);

            foreach ($ids[1] as $id) {
                $this->assertStringEndsWith('-'.$name, $id, "شناسه {$id} در {$name} ممکن است با تصویر دیگری در همان صفحه برخورد کند.");
            }
        }
    }

    public function test_a_picture_renders_as_decorative_inline_svg(): void
    {
        $html = Blade::render('<x-art name="projects-index" class="h-24" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('h-24', $html);
    }

    public function test_an_unknown_or_malformed_name_draws_nothing(): void
    {
        $this->assertSame('', trim(Blade::render('<x-art name="nope" />')));
        $this->assertSame('', trim(Blade::render('<x-art name="../art/index" />')));
    }

    public function test_the_homepage_and_the_404_page_carry_the_drawings(): void
    {
        $this->get('/')->assertOk()->assertSee('filter="url(#r-home-hero)"', false);
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('filter="url(#r-errors-404)"', false);
    }
}
