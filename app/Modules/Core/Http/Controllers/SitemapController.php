<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Seo\SitemapBuilder;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Response;

/**
 * نقشه سایت.
 *
 * کش می‌شود چون هر بار ساختنش یعنی چند پرس‌وجو روی هر ماژول محتوایی، و
 * موتورهای جست‌وجو این نشانی را مرتب می‌خوانند.
 */
final readonly class SitemapController
{
    private const CACHE_KEY = 'core.sitemap.xml';

    public function __construct(
        private SitemapBuilder $builder,
        private Cache $cache,
    ) {}

    public function __invoke(): Response
    {
        $seconds = (int) config('core.seo.sitemap.cache_seconds', 3600);

        $xml = $seconds > 0
            ? $this->cache->remember(self::CACHE_KEY, $seconds, fn (): string => $this->builder->toXml())
            : $this->builder->toXml();

        return response($xml, Response::HTTP_OK, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
