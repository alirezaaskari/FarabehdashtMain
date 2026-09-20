<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پوسته سایت — سربرگ، فوتر و ستون کناری.
 *
 * این پوسته روی همه صفحه‌ها می‌نشیند، پس هر وابستگی محافظت‌نشده‌اش به یک
 * ماژول، با خاموش‌شدن آن ماژول کل سایت را می‌شکند. قاعده ۲ می‌گوید حذف یک
 * ماژول باید حذف پوشه به‌علاوه یک خط از config/modules.php باشد و بس.
 */
final class SiteShellTest extends TestCase
{
    use RefreshDatabase;

    /** فایل‌هایی که روی هر صفحه رندر می‌شوند و حق ندارند مسیر را فرض بگیرند. */
    private const array SHELL = [
        'resources/views/components/site/header.blade.php',
        'resources/views/components/site/footer.blade.php',
        'resources/views/components/site/sidebar.blade.php',
    ];

    public function test_the_shell_never_calls_a_route_it_has_not_checked_for(): void
    {
        foreach (self::SHELL as $file) {
            $source = (string) file_get_contents(base_path($file));

            preg_match_all("/route\('([a-z0-9_.\-]+)'/i", $source, $matches);

            foreach (array_unique($matches[1]) as $name) {
                $this->assertStringContainsString(
                    "Route::has('{$name}')",
                    $source,
                    "«{$file}» مسیر «{$name}» را بدون Route::has فراخوانی می‌کند؛ "
                        .'با خاموش‌شدن ماژول صاحب آن مسیر، همه صفحه‌ها ۵۰۰ می‌دهند.',
                );
            }
        }
    }

    public function test_the_public_shell_carries_the_whole_site_navigation(): void
    {
        $this->get('/tools')
            ->assertOk()
            ->assertSee('دانشنامه')
            ->assertSee('مواد شیمیایی')
            ->assertSee('مشاوره')
            ->assertSee('تمامی حقوق محفوظ است', escape: false);
    }

    public function test_the_workspace_shell_keeps_the_same_header_and_footer(): void
    {
        $this->actingAs($this->user())
            ->get(route('tools.calculations.index'))
            ->assertOk()
            ->assertSee('دانشنامه')
            ->assertSee('میزکار')
            ->assertSee('تمامی حقوق محفوظ است', escape: false);
    }

    private function user(): User
    {
        return User::factory()->create();
    }
}
