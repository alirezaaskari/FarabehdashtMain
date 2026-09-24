<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Tests\TestCase;

/**
 * قاعده محصول: مسیری که نامش با فایلی در `public/` یکی باشد در production
 * اجرا نمی‌شود — وب‌سرور فایل یا پوشه را پیش از رسیدن به PHP جواب می‌دهد.
 *
 * SeoTest همین را برای robots.txt و sitemap.xml می‌پاید؛ این تست همه مسیرها را.
 */
final class PublicPathShadowTest extends TestCase
{
    public function test_no_route_shares_its_first_segment_with_a_public_file(): void
    {
        $public = array_values(array_diff(scandir(public_path()) ?: [], ['.', '..', 'index.php']));
        $shadowed = [];

        /** @var Route $route */
        foreach ($this->app->make(Router::class)->getRoutes() as $route) {
            $first = explode('/', trim($route->uri(), '/'))[0];

            if ($first !== '' && ! str_starts_with($first, '{') && in_array($first, $public, true)) {
                $shadowed[] = $route->uri();
            }
        }

        $this->assertContains('build', $public, 'باید پوشه public را واقعاً دیده باشد.');
        $this->assertSame([], $shadowed, 'این مسیرها پشت فایل یا پوشه public می‌مانند: '.implode('، ', $shadowed));
    }
}
