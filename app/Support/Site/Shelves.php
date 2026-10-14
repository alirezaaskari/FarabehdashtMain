<?php

declare(strict_types=1);

namespace App\Support\Site;

use App\Contracts\ShelfSource;
use Illuminate\Database\Connection;
use Illuminate\Routing\Router;

/**
 * کدام فهرست‌های عمومی سایت چیزی برای نشان‌دادن دارند.
 *
 * سربرگ، نوار پایین، فوتر و صفحه اصلی هر پیوند ورود به فهرست را با
 * {@see self::open()} می‌سنجند. پاسخ همه فهرست‌ها با یک پرس‌وجو می‌آید و
 * برای همان درخواست نگه داشته می‌شود، پس بودجه پرس‌وجوی صفحه‌ها دست نمی‌خورد.
 * بی‌کش: اولین آگهی تأییدشده همان لحظه در منو پیدا می‌شود.
 */
final class Shelves
{
    /** @var array<string, bool>|null */
    private ?array $filled = null;

    /**
     * @param  iterable<ShelfSource>  $sources
     */
    public function __construct(
        private readonly iterable $sources,
        private readonly Connection $db,
        private readonly Router $router,
    ) {}

    /** مسیر هست و فهرستش خالی نیست؛ مسیری که منبعی ندارد همیشه باز است. */
    public function open(string $route): bool
    {
        return $this->router->has($route) && ($this->filled()[$route] ?? true);
    }

    /** @return array<string, bool> */
    private function filled(): array
    {
        if ($this->filled !== null) {
            return $this->filled;
        }

        $queries = [];
        foreach ($this->sources as $source) {
            $queries += $source->shelves();
        }

        if ($queries === []) {
            return $this->filled = [];
        }

        $select = $this->db->query();
        $aliases = [];
        foreach (array_keys($queries) as $index => $route) {
            $aliases["shelf_{$index}"] = $route;
            $query = $queries[$route];
            $select->selectRaw("exists({$query->toSql()}) as shelf_{$index}", $query->getBindings());
        }

        $row = (array) $select->first();

        $filled = [];
        foreach ($aliases as $alias => $route) {
            $filled[$route] = (bool) ($row[$alias] ?? true);
        }

        return $this->filled = $filled;
    }
}
