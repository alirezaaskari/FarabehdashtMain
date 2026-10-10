<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use InvalidArgumentException;

/**
 * شکل خطی بدن کنار هر گام فرم پوسچر، با عضوی که آن گام می‌پرسد پررنگ.
 *
 * کاربر با یک نگاه می‌فهمد «حالا درباره کدام عضو می‌پرسد»؛ زاویه دقیق کار
 * کارت‌های گزینه است ({@see AngleFigure}). هم‌جهت با آن شکل‌ها، رو به چپ.
 * ایستاده برای RULA و REBA، نشسته پشت میز برای ROSA؛ در حالت نشسته
 * صندلی، میز و مانیتور هم بخش‌اند تا گام «دسته صندلی» چیزی برای نشان دادن
 * داشته باشد.
 *
 * بوم ۶۰×۱۰۰ و محور y رو به پایین، مثل SVG.
 */
final readonly class BodyFigure
{
    public const string STANDING = 'standing';

    public const string SEATED = 'seated';

    /**
     * هر حالت: بخش ← مسیرها. سر جزو گردن است، چون گام گردن درباره سر هم می‌پرسد.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const array PARTS = [
        self::STANDING => [
            'neck' => ['M27 12a7 7 0 1 0 14 0a7 7 0 1 0 -14 0', 'M34 19V25'],
            'trunk' => ['M34 25V56'],
            'upper_arm' => ['M34 29L32 46'],
            'lower_arm' => ['M32 46L20 50'],
            'wrist' => ['M20 50L14 52'],
            'legs' => ['M34 56V95H27'],
        ],
        self::SEATED => [
            'neck' => ['M31 14a7 7 0 1 0 14 0a7 7 0 1 0 -14 0', 'M38 21V27'],
            'trunk' => ['M38 27L37 58'],
            'upper_arm' => ['M38 31L35 47'],
            'lower_arm' => ['M35 47L20 46'],
            'wrist' => ['M20 46H14'],
            'legs' => ['M37 58H18V82H12'],
            'seat' => ['M46 62H16'],
            'backrest' => ['M46 62L48 30'],
            'armrest' => ['M45 50H30'],
            'desk' => ['M24 49H2', 'M4 49V95'],
            'screen' => ['M4 10H14V26H4Z', 'M9 26V49'],
        ],
    ];

    /** بخش‌هایی که بدن نیستند و نازک کشیده می‌شوند تا بدن در شکل گم نشود. */
    private const array FURNITURE = ['seat', 'backrest', 'armrest', 'desk', 'screen'];

    /** پایه صندلی؛ همیشه زمینه است و هیچ گامی درباره‌اش نمی‌پرسد. */
    private const array GROUND = [self::SEATED => ['M31 62V90', 'M22 90H40']];

    /**
     * @param  list<string>  $body  عضوهای بی‌رنگ
     * @param  list<string>  $furniture  صندلی و میز بی‌رنگ، نازک
     * @param  list<string>  $marked  آنچه این گام می‌پرسد
     */
    private function __construct(
        public array $body,
        public array $furniture,
        public array $marked,
    ) {}

    /**
     * @param  list<string>  $highlight
     */
    public static function of(string $pose, array $highlight): self
    {
        $parts = self::PARTS[$pose] ?? throw new InvalidArgumentException(sprintf('حالت بدن «%s» شکل ندارد.', $pose));

        foreach ($highlight as $part) {
            if (! isset($parts[$part])) {
                throw new InvalidArgumentException(sprintf('بخش «%s» در شکل %s نیست.', $part, $pose));
            }
        }

        $body = [];
        $furniture = self::GROUND[$pose] ?? [];
        $marked = [];

        foreach ($parts as $part => $paths) {
            if (in_array($part, $highlight, true)) {
                array_push($marked, ...$paths);
            } elseif (in_array($part, self::FURNITURE, true)) {
                array_push($furniture, ...$paths);
            } else {
                array_push($body, ...$paths);
            }
        }

        return new self($body, $furniture, $marked);
    }

    /**
     * @return list<string>
     */
    public static function parts(string $pose): array
    {
        return array_keys(self::PARTS[$pose] ?? []);
    }
}
