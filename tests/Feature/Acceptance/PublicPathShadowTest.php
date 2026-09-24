<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * قاعده محصول: مسیری که نامش با فایلی در `public/` یکی باشد در production
 * اجرا نمی‌شود — وب‌سرور فایل یا پوشه را پیش از رسیدن به PHP جواب می‌دهد.
 *
 * پوشه فقط نشانی دقیق خودش را می‌گیرد: `.htaccess` هر نشانی زیر آن که فایلی
 * برایش نیست به index.php می‌دهد. پس `storage/{path}` لاراول کنار پیوند
 * `public/storage` (ساخته `storage:link` در deploy.sh) سالم است، ولی مسیر
 * ثابت `/build` یا `/robots.txt` کنار پوشه یا فایل هم‌نامش نه.
 *
 * SeoTest همین را برای robots.txt و sitemap.xml می‌پاید؛ این تست همه مسیرها را.
 */
final class PublicPathShadowTest extends TestCase
{
    public function test_no_route_is_shadowed_by_a_public_file_or_folder(): void
    {
        $this->assertDirectoryExists(public_path('build'), 'باید پوشه public را واقعاً دیده باشد.');
        $this->assertSame([], $this->shadowedRoutes(), 'این مسیرها پشت فایل یا پوشه public می‌مانند.');
    }

    public function test_the_storage_link_made_by_deploy_does_not_count_as_a_shadow(): void
    {
        $link = public_path('storage');
        $created = ! file_exists($link);

        if ($created) {
            File::makeDirectory($link);
        }

        try {
            $this->assertSame([], $this->shadowedRoutes());
        } finally {
            if ($created) {
                File::deleteDirectory($link);
            }
        }
    }

    /** @return list<string> */
    private function shadowedRoutes(): array
    {
        $shadowed = [];

        /** @var Route $route */
        foreach ($this->app->make(Router::class)->getRoutes() as $route) {
            $uri = trim($route->uri(), '/');
            $static = rtrim(explode('{', $uri)[0], '/');

            if ($static === '') {
                continue;
            }

            // مسیر ثابت با هر فایل یا پوشه هم‌نام؛ مسیر پارامتردار فقط وقتی
            // بخش ثابتش خودِ یک فایل است.
            $path = public_path($static);
            $hit = $static === $uri ? file_exists($path) : is_file($path);

            if ($hit) {
                $shadowed[] = $route->uri();
            }
        }

        return $shadowed;
    }
}
