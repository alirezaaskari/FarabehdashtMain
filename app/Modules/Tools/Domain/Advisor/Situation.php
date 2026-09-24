<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Advisor;

use App\Modules\Tools\Domain\ResolvedTool;

/**
 * سؤال سوم دستیار: یک موقعیت واقعی میدانی، با ابزارش و دلیلش.
 *
 * کسی که اسم ابزار را نمی‌داند، موقعیت خودش را می‌شناسد — «چند دستگاه
 * هم‌زمان کار می‌کنند» را می‌فهمد، «جمع لگاریتمی» را نه.
 */
final readonly class Situation
{
    public function __construct(
        public string $text,
        public string $reason,
        public ResolvedTool $tool,
    ) {}

    public function key(): string
    {
        return $this->tool->slug();
    }
}
