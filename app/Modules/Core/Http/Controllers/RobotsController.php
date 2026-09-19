<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;

/**
 * robots.txt
 *
 * قاعده: هر محیطی که production نیست، کامل بسته است. نسخه آزمایشی سایتی که
 * ایندکس شود، ماه‌ها محتوای تکراری تولید می‌کند و برگرداندنش سخت است.
 */
final readonly class RobotsController
{
    public function __construct(private Application $app) {}

    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        if ($this->app->isProduction()) {
            /** @var list<string> $disallow */
            $disallow = (array) config('core.seo.robots.disallow', []);

            foreach ($disallow as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
