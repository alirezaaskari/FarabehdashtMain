<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Services;

use InvalidArgumentException;

/**
 * هیچ بسته‌ای وعده قبولی نمی‌دهد (بخش ۱۸-۷). متنی که مدیر برای عنوان و
 * معرفی بسته می‌نویسد پیش از ذخیره از این صافی می‌گذرد.
 */
final readonly class NoPromises
{
    /** @param  list<string>  $phrases */
    public function __construct(private array $phrases) {}

    public function assert(string ...$texts): void
    {
        foreach ($texts as $text) {
            foreach ($this->phrases as $phrase) {
                if ($phrase !== '' && mb_stripos($text, $phrase) !== false) {
                    throw new InvalidArgumentException(sprintf(
                        'متن بسته نباید وعده قبولی بدهد؛ «%s» را بردارید. نتیجه آزمون به خود داوطلب بستگی دارد.',
                        $phrase,
                    ));
                }
            }
        }
    }
}
