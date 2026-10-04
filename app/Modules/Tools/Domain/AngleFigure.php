<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use InvalidArgumentException;

/**
 * شکل خطی بازه زاویه یک عضو، برای کارت‌های گزینه فرم‌های پوسچر.
 *
 * نقاشی دستی نیست، هندسه است: مفصل، خط چین وضعیت خنثی، گوه بازه و خود عضو
 * در وسط بازه. پس زاویه روی شکل همان عددی است که برچسب گزینه می‌گوید.
 * چهره رو به چپ است؛ خم شدن به جلو (فلکشن) زاویه مثبت است.
 *
 * بوم ۱۰۰×۱۰۰ و محور y رو به پایین، مثل SVG.
 */
final readonly class AngleFigure
{
    private const float SEGMENT = 30.0;

    private const float WEDGE = 40.0;

    /** گوه عضوی که سر در انتهایش است کوتاه‌تر است تا زیر سر پنهان نشود. */
    private const float WEDGE_UNDER_HEAD = 24.0;

    /**
     * هر عضو: مفصل، زاویه صفحه در وضعیت خنثی، جهت فلکشن، و بدن پیرامون برای جهت‌یابی.
     *
     * @var array<string, array{pivot: array{float, float}, neutral: float, sign: float, context: list<string>, head: array{float, float}|null, tip: bool}>
     */
    private const array SEGMENTS = [
        // شانه؛ گردن و سر بالای آن، بازو آویزان رو به پایین، فلکشن به جلو.
        'upper_arm' => ['pivot' => [50.0, 50.0], 'neutral' => 90.0, 'sign' => 1.0, 'context' => ['M50 50 L50 34'], 'head' => [50.0, 24.0], 'tip' => false],
        // آرنج؛ بازو بالای آن، زاویه ساعد از امتداد بازو.
        'lower_arm' => ['pivot' => [50.0, 50.0], 'neutral' => 90.0, 'sign' => 1.0, 'context' => ['M50 50 L50 8'], 'head' => null, 'tip' => false],
        // مچ؛ ساعد افقی از راست، دست رو به جلو. فلکشن یعنی کف دست رو به پایین.
        'wrist' => ['pivot' => [50.0, 50.0], 'neutral' => 180.0, 'sign' => -1.0, 'context' => ['M94 50 L50 50'], 'head' => null, 'tip' => false],
        // پایه گردن؛ تنه زیر آن، سر سر عضو.
        'neck' => ['pivot' => [50.0, 62.0], 'neutral' => 270.0, 'sign' => -1.0, 'context' => ['M50 62 L50 98'], 'head' => null, 'tip' => true],
        // لگن؛ پاها زیر آن، تنه رو به بالا.
        'trunk' => ['pivot' => [50.0, 66.0], 'neutral' => 270.0, 'sign' => -1.0, 'context' => ['M50 66 L50 98'], 'head' => null, 'tip' => true],
    ];

    /**
     * @param  list<string>  $context  مسیرهای بدن پیرامون
     * @param  array{float, float, float, float}  $reference  خط خنثی
     * @param  list<string>  $wedges  مسیر گوه هر بازه
     * @param  list<array{float, float, float, float}>  $limbs  عضو در وسط هر بازه
     * @param  array{float, float}|null  $head  سر بدن پیرامون، وقتی سر جزو عضو نیست
     * @param  list<array{float, float}>  $tips  سر در انتهای عضو (گردن و تنه)
     */
    private function __construct(
        public array $context,
        public array $reference,
        public array $wedges,
        public array $limbs,
        public ?array $head,
        public array $tips,
    ) {}

    /**
     * @param  list<array{float, float}>  $ranges  بازه‌های زاویه به درجه؛ [۰، ۰] یعنی خود وضعیت خنثی
     */
    public static function of(string $segment, array $ranges): self
    {
        $spec = self::SEGMENTS[$segment] ?? throw new InvalidArgumentException(sprintf('عضو «%s» شکل ندارد.', $segment));
        [$x, $y] = $spec['pivot'];
        $screen = static fn (float $angle): float => $spec['neutral'] + $spec['sign'] * $angle;

        $wedges = [];
        $limbs = [];
        $tips = [];

        foreach ($ranges as [$from, $to]) {
            if ($from !== $to) {
                $wedges[] = self::wedge($x, $y, $screen($from), $screen($to), $spec['tip'] ? self::WEDGE_UNDER_HEAD : self::WEDGE);
            }

            [$endX, $endY] = self::point($x, $y, $screen(($from + $to) / 2), self::SEGMENT);
            $limbs[] = [$x, $y, $endX, $endY];

            if ($spec['tip']) {
                $tips[] = self::point($x, $y, $screen(($from + $to) / 2), self::SEGMENT + 7.0);
            }
        }

        [$refX, $refY] = self::point($x, $y, $spec['neutral'], self::WEDGE);

        return new self($spec['context'], [$x, $y, $refX, $refY], $wedges, $limbs, $spec['head'], $tips);
    }

    /** @return list<string> */
    public static function segments(): array
    {
        return array_keys(self::SEGMENTS);
    }

    private static function wedge(float $x, float $y, float $start, float $end, float $radius): string
    {
        [$startX, $startY] = self::point($x, $y, $start, $radius);
        [$endX, $endY] = self::point($x, $y, $end, $radius);

        return sprintf(
            'M%s %s L%s %s A%s %s 0 %d %d %s %s Z',
            self::n($x), self::n($y), self::n($startX), self::n($startY),
            self::n($radius), self::n($radius),
            abs($end - $start) > 180.0 ? 1 : 0,
            $end > $start ? 1 : 0,
            self::n($endX), self::n($endY),
        );
    }

    /** @return array{float, float} */
    private static function point(float $x, float $y, float $angle, float $length): array
    {
        $radians = deg2rad($angle);

        return [round($x + $length * cos($radians), 2), round($y + $length * sin($radians), 2)];
    }

    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
