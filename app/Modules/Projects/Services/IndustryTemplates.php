<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\IndustryTemplate;

/**
 * قالب‌های صنعتی، از پیکربندی.
 *
 * صنعتی که قالب ندارد خطا نیست: پروژه بدون ایستگاه پیش‌فرض ساخته می‌شود و
 * کاربر خودش می‌سازدشان.
 */
final readonly class IndustryTemplates
{
    /**
     * @param  array<string, array<string, mixed>>  $templates
     */
    public function __construct(private array $templates) {}

    public function for(Industry $industry): ?IndustryTemplate
    {
        $config = $this->templates[$industry->value] ?? null;

        if ($config === null) {
            return null;
        }

        /** @var list<string> $stations */
        $stations = $config['stations'] ?? [];

        /** @var list<string> $tools */
        $tools = $config['tools'] ?? [];

        return new IndustryTemplate(
            industry: $industry,
            stations: $stations,
            tools: $tools,
            note: (string) ($config['note'] ?? ''),
        );
    }

    /**
     * @return list<IndustryTemplate>
     */
    public function all(): array
    {
        $all = [];

        foreach (Industry::cases() as $industry) {
            $template = $this->for($industry);

            if ($template !== null) {
                $all[] = $template;
            }
        }

        return $all;
    }
}
