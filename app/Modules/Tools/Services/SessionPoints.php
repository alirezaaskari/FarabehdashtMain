<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\ResultRow;
use App\Modules\Tools\Domain\SessionPoint;
use Illuminate\Contracts\Session\Session;

/**
 * «نقطه‌های این جلسه»: محاسبه‌های پشت‌سرهم یک ابزار در همین جلسه مرورگر.
 *
 * کارشناس در کارگاه چند نقطه پشت هم اندازه می‌گیرد و می‌خواهد عددها را کنار
 * هم ببیند، بی‌آنکه هر کدام را ذخیره کند. داده فقط در نشست سرور می‌ماند،
 * به حساب وصل نیست و با بستن مرورگر یا دکمه «پاک کردن» می‌رود.
 */
final readonly class SessionPoints
{
    private const LIMIT = 10;

    /**
     * @param  array<string, mixed>  $submitted
     * @return list<SessionPoint>
     */
    public function record(Session $session, string $slug, array $submitted, ResultRow $primary): array
    {
        $inputs = array_map(strval(...), array_filter($submitted, is_scalar(...)));

        $points = [...$this->for($session, $slug), new SessionPoint($inputs, $primary->value, $primary->unit)];
        $points = array_slice($points, -self::LIMIT);

        $session->put($this->key($slug), array_map(static fn (SessionPoint $p): array => $p->toArray(), $points));

        return $points;
    }

    /** @return list<SessionPoint> */
    public function for(Session $session, string $slug): array
    {
        $stored = $session->get($this->key($slug), []);

        if (! is_array($stored)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): ?SessionPoint => is_array($item) ? SessionPoint::fromArray($item) : null,
            $stored,
        )));
    }

    public function clear(Session $session, string $slug): void
    {
        $session->forget($this->key($slug));
    }

    private function key(string $slug): string
    {
        return 'tools.points.'.$slug;
    }
}
