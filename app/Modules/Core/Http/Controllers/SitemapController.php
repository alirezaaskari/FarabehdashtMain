<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Seo\SitemapBuilder;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * نقشه سایت: فهرست فایل‌ها و فایل هر بخش.
 *
 * همه فایل‌ها با هم ساخته و با هم کش می‌شوند: فهرست به `lastmod` هر بخش نیاز
 * دارد، و ساختن جداگانه یعنی فهرست و فایل‌ها ممکن است از دو لحظه متفاوت باشند.
 */
final readonly class SitemapController
{
    public const CACHE_KEY = 'core.sitemap.files';

    public function __construct(
        private SitemapBuilder $builder,
        private Cache $cache,
    ) {}

    public function index(): Response
    {
        return $this->xml($this->rendered()['index']);
    }

    public function file(string $name): Response
    {
        $xml = $this->rendered()['files'][$name] ?? throw new NotFoundHttpException('چنین نقشه‌ای نیست.');

        return $this->xml($xml);
    }

    /** @return array{index: string, files: array<string, string>} */
    private function rendered(): array
    {
        $seconds = (int) config('core.seo.sitemap.cache_seconds', 3600);

        return $seconds > 0
            ? $this->cache->remember(self::CACHE_KEY, $seconds, $this->render(...))
            : $this->render();
    }

    /** @return array{index: string, files: array<string, string>} */
    private function render(): array
    {
        $files = $this->builder->files();

        return [
            'index' => $this->builder->indexXml($files),
            'files' => array_map($this->builder->urlsetXml(...), $files),
        ];
    }

    private function xml(string $body): Response
    {
        return response($body, Response::HTTP_OK, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
