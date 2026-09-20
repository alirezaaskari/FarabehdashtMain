<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use App\Modules\Projects\Domain\Enums\Industry;

/**
 * قالب پیشنهادی یک صنعت.
 *
 * ⚠️ فهرست ایستگاه‌ها و ابزارها **پیشنهادی و عمومی** است. تعیین دامنه واقعی
 * پایش بر عهده کارشناس و بر اساس شناسایی عوامل زیان‌آور همان واحد است.
 */
final readonly class IndustryTemplate
{
    /**
     * @param  list<string>  $stations
     * @param  list<string>  $tools  شناسه ابزار — این ماژول هرگز حلش نمی‌کند
     */
    public function __construct(
        public Industry $industry,
        public array $stations,
        public array $tools,
        public string $note,
    ) {}

    public function label(): string
    {
        return $this->industry->label();
    }
}
