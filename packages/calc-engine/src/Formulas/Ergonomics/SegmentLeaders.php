<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Ergonomics;

/**
 * جمله «بیشترین سهم» روش‌های پوسچر: عضوهایی که امتیازشان نسبت به بیشینه
 * خودشان از همه بالاتر است، با رقم فارسی چون داخل جمله فارسی می‌نشیند.
 */
final class SegmentLeaders
{
    /**
     * @param  array<string, int>  $segments  امتیاز هر عضو
     * @param  array<string, array{string, int}>  $spec  عضو → [نام، بیشینه امتیاز]، به ترتیب نمایش
     * @return string رشته خالی وقتی همه عضوها در کمترین امتیاز (۱) باشند
     */
    public static function describe(array $segments, array $spec): string
    {
        $best = 0.0;
        $names = [];
        $label = static fn (string $name, int $score, int $max): string => sprintf('%s (%s از %s)', $name, self::persian($score), self::persian($max));

        foreach ($spec as $key => [$name, $max]) {
            if ($segments[$key] <= 1) {
                continue;
            }

            $ratio = $segments[$key] / $max;

            if (abs($ratio - $best) < 1e-9) {
                $names[] = $label($name, $segments[$key], $max);
            } elseif ($ratio > $best) {
                $best = $ratio;
                $names = [$label($name, $segments[$key], $max)];
            }
        }

        return self::join($names);
    }

    /**
     * فهرست فارسی: «الف»، «الف و ب»، «الف، ب و ج».
     *
     * @param  list<string>  $names
     */
    public static function join(array $names): string
    {
        if (count($names) < 2) {
            return $names[0] ?? '';
        }

        $last = array_pop($names);

        return implode('، ', $names).' و '.$last;
    }

    /** موتور ابزار بومی‌سازی ندارد. */
    private static function persian(int $number): string
    {
        return strtr((string) $number, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
