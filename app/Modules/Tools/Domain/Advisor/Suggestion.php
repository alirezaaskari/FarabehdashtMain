<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Advisor;

/**
 * یک ردیف پنل «نتیجه پیشنهادی».
 *
 * `kind` برچسب کوتاه نوع است (ابزار، مقاله، فایل، دوره، ماده، اقدام) و
 * `note` دلیل یک‌خطی، وقتی پیشنهاد دلیل روشن دارد.
 */
final readonly class Suggestion
{
    public function __construct(
        public string $kind,
        public string $title,
        public string $url,
        public string $note = '',
    ) {}
}
