<?php

declare(strict_types=1);

namespace App\Support\Help\Motion;

use InvalidArgumentException;

/**
 * زمان‌بندی یک آموزش متحرک از روی تعریفش.
 *
 * تعریف فقط می‌گوید «چه چیزی به چه ترتیبی»: صحنه‌ها و اجزای هر صحنه (فهرست
 * انتخابی، فیلد، دکمه، جدول…). این کلاس برای هر جزء زمان پیدا شدن، کلیک و تایپ
 * را حساب می‌کند و مسیر نشانگر را می‌سازد؛ پس همه آموزش‌ها یک ضرباهنگ دارند و
 * نویسنده تعریف هیچ عدد ثانیه‌ای نمی‌نویسد. پخش در resources/js/motion.js است.
 *
 * شکل تعریف و انواع جزء در {@see MotionTutorials} توضیح داده شده است.
 */
final class MotionScript
{
    /** زمان حرکت نشانگر تا هدف بعدی. */
    private const MOVE = 1.0;

    /** مکث پس از هر کلیک. */
    private const AFTER_CLICK = 0.45;

    /** ثانیه برای هر نویسه متن زیرنویس؛ صحنه کوتاه‌تر از زمان خواندنش نمی‌شود. */
    private const READ_PER_CHAR = 0.055;

    private const MIN_SCENE = 5.0;

    private const CARD_SCENE = 4.5;

    /** جای استراحت نشانگر در گوشه صحنه (مختصات صحنه ۶۴۰×۴۴۰). */
    private const REST = [560, 420];

    /**
     * @param  list<array<string, mixed>>  $scenes  صحنه‌ها با زمان و اجزای زمان‌دار
     * @param  list<array{0: float, 1: string|list<int>|null, 2?: bool}>  $path  مسیر نشانگر
     * @param  list<string>  $steps  برچسب نوار مراحل، اگر آموزش مرحله‌ای باشد
     * @param  list<array{url: string, show: string}>  $urls  نشانی نوار بالای پنجره در هر بازه
     */
    private function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly float $duration,
        public readonly float $poster,
        public readonly array $scenes,
        public readonly array $path,
        public readonly array $steps,
        public readonly array $urls,
        public readonly ?string $window,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function build(string $key, array $definition): self
    {
        $title = (string) ($definition['title'] ?? throw new InvalidArgumentException("آموزش {$key} عنوان ندارد."));
        $steps = array_values(array_map('strval', (array) ($definition['steps'] ?? [])));
        $defaultUrl = (string) ($definition['url'] ?? 'farabehdasht.com');

        $raw = [];

        if (isset($definition['intro'])) {
            $raw[] = ['kind' => 'card', 'chapter' => null, ...(array) $definition['intro']];
        }

        foreach ((array) ($definition['scenes'] ?? []) as $scene) {
            $raw[] = ['kind' => 'window', ...(array) $scene];
        }

        if (isset($definition['outro'])) {
            $raw[] = ['kind' => 'card', 'chapter' => null, ...(array) $definition['outro']];
        }

        $t = 0.0;
        $scenes = [];
        $path = [];
        $urls = [];
        $target = 0;
        $windowStart = null;
        $windowEnd = null;

        foreach ($raw as $index => $scene) {
            $caption = trim((string) ($scene['caption'] ?? ''));

            if ($caption === '') {
                throw new InvalidArgumentException("صحنه {$index} آموزش {$key} زیرنویس ندارد.");
            }

            $start = $t;
            $minimum = max(self::MIN_SCENE, mb_strlen($caption) * self::READ_PER_CHAR);

            if ($scene['kind'] === 'card') {
                $end = $start + max(self::CARD_SCENE, min($minimum, 7.0));
                $scene['chips'] = $index === 0 ? $steps : [];

                if ($path !== []) {
                    $path[] = [$start + 0.4, null];
                }
            } elseif ($scene['kind'] === 'window' && isset($scene['phone'])) {
                $scene['kind'] = 'phone';
                $end = $start + max($minimum, 7.5);
                $path[] = [$start + 0.2, null];
            } else {
                $windowStart ??= $start;
                $cursor = $start + 0.6;

                if ($path === [] || end($path)[1] === null) {
                    $path[] = [$start + 0.3, self::REST];
                }

                [$scene['widgets'], $cursor] = self::schedule(
                    (array) ($scene['widgets'] ?? []),
                    $start,
                    $cursor,
                    $path,
                    $target,
                );

                $end = max($cursor + 0.9, $start + $minimum);
                $windowEnd = $end;
                $urls[] = ['url' => (string) ($scene['url'] ?? $defaultUrl), 'show' => self::range($start, $end)];
            }

            $scene['start'] = round($start, 2);
            $scene['end'] = round($end, 2);
            $scene['show'] = self::range($start, $end);
            $scene['caption'] = $caption;
            $scenes[] = $scene;
            $t = $end;
        }

        // برگه‌ای که از پنجره بیرون می‌آید (sheet) تا پایان صحنه گوشی بعدی می‌ماند.
        foreach ($scenes as $i => $scene) {
            foreach ((array) ($scene['widgets'] ?? []) as $w => $widget) {
                if (($widget['type'] ?? null) === 'sheet') {
                    $next = $scenes[$i + 1] ?? null;
                    // اگر گوشی تصویر کناری دارد، برگه جایش را به آن می‌دهد؛ قالب انتخاب می‌کند.
                    $phone = $next !== null && $next['kind'] === 'phone';
                    $scenes[$i]['widgets'][$w]['show'] = self::range($widget['at'], $phone ? $next['end'] : $scene['end']);
                    $scenes[$i]['widgets'][$w]['show_before_side'] = self::range($widget['at'], $phone ? $next['start'] + 3.8 : $scene['end']);
                    $scenes[$i]['widgets'][$w]['side'] = $phone ? ($next['phone']['side'] ?? null) : null;
                }
            }
        }

        // مراحل: مرحله صحنه روشن، مرحله‌های پیش از آن «انجام‌شده».
        $stepRules = array_fill(0, count($steps), []);

        foreach ($scenes as $scene) {
            if (! isset($scene['step']) || $scene['kind'] !== 'window') {
                continue;
            }

            $range = sprintf('%s-%s', self::n($scene['start']), self::n($scene['end']));

            foreach (array_keys($steps) as $i) {
                if ($i === $scene['step']) {
                    $stepRules[$i][] = 'is-on@'.$range;
                } elseif ($i < $scene['step']) {
                    $stepRules[$i][] = 'is-done@'.$range;
                }
            }
        }

        $path[] = [round($t, 2), null];

        // تصویر پیش از پخش: کارت آغاز کامل، یا میانه صحنه اول.
        $first = $scenes[0] ?? ['kind' => 'card', 'start' => 0.0, 'end' => 0.0];
        $poster = $first['kind'] === 'card'
            ? max(0.0, min($first['end'] - 1.2, 3.0))
            : min($first['end'] - 0.5, $first['start'] + 3.5);

        return new self(
            key: $key,
            title: $title,
            duration: round($t, 2),
            poster: round($poster, 2),
            scenes: array_map(function (array $scene) use ($stepRules): array {
                $scene['step_rules'] = $stepRules;

                return $scene;
            }, $scenes),
            path: array_map(fn (array $p): array => [round($p[0], 2), ...array_slice($p, 1)], $path),
            steps: $steps,
            urls: $urls,
            window: $windowStart !== null && $windowEnd !== null
                ? self::range($windowStart, $windowEnd)
                : null,
        );
    }

    /**
     * زمان هر جزء صحنه پنجره‌ای.
     *
     * جزء نمایشی پیش از اولین کار کاربر از آغاز صحنه دیده می‌شود؛ پس از آن، همان
     * لحظه‌ای که نوبتش برسد، یعنی نتیجه همان کلیک یا تایپ است.
     *
     * @param  list<array<string, mixed>>|array<mixed>  $widgets
     * @param  list<array{0: float, 1: string|list<int>|null, 2?: bool}>  $path
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private static function schedule(array $widgets, float $start, float $t, array &$path, int &$target): array
    {
        $acted = false;
        $out = [];

        $click = function (string $id) use (&$t, &$path, &$acted): float {
            $at = $t + self::MOVE;
            $path[] = [$at, $id, true];
            $acted = true;

            return $at + 0.15;
        };

        $reveal = function (float $gap = 0.4) use (&$t, &$acted, $start): float {
            if (! $acted) {
                return $start + 0.1;
            }

            $at = $t;
            $t += $gap;

            return $at;
        };

        foreach ($widgets as $widget) {
            $widget = (array) $widget;
            $type = (string) ($widget['type'] ?? throw new InvalidArgumentException('جزء بدون نوع.'));
            $id = 'w'.(++$target);
            $widget['id'] = $id;

            switch ($type) {
                case 'heading':
                case 'text':
                    $widget['at'] = $reveal(0.3);
                    break;

                case 'choice':
                    $widget['at'] = $reveal(0.5);

                    if (isset($widget['pick'])) {
                        $widget['picked_at'] = $click($id.'-'.$widget['pick']);
                        $t = $widget['picked_at'] + self::AFTER_CLICK;
                    }

                    break;

                case 'search':
                case 'fields':
                case 'textarea':
                    $widget['at'] = $reveal(0.3);
                    $fields = $type === 'fields' ? (array) $widget['items'] : [$widget];

                    foreach ($fields as $i => $field) {
                        $field = (array) $field;

                        if (($field['typed'] ?? $type !== 'fields') === true) {
                            $focus = $click($id.'-'.$i);
                            $length = mb_strlen((string) $field['value']);
                            $speed = $type === 'textarea' ? 0.04 : 0.065;
                            $duration = min(max($length * $speed, 0.6), $type === 'textarea' ? 4.0 : 2.4);
                            $field['type_at'] = self::range($focus + 0.1, $focus + 0.1 + $duration);
                            $field['focus_at'] = self::range($focus - 0.05, $focus + $duration + 0.5);
                            $t = $focus + $duration + 0.35;
                        }

                        $fields[$i] = $field;
                    }

                    if ($type === 'fields') {
                        $widget['items'] = $fields;
                    } else {
                        $widget = [...$widget, ...$fields[0]];
                    }

                    break;

                case 'button':
                    if (($widget['click'] ?? true) === false) {
                        $widget['at'] = $reveal(0.3);
                        $widget['on_at'] = -1.0;
                        break;
                    }

                    $widget['at'] = $reveal(0.3);
                    $widget['on_at'] = $click($id);
                    $t = $widget['on_at'] + 0.6;
                    break;

                case 'toggle':
                case 'check':
                    $widget['at'] = $reveal(0.3);
                    $widget['on_at'] = $click($id);
                    $t = $widget['on_at'] + self::AFTER_CLICK;
                    break;

                case 'table':
                    $rows = count((array) $widget['rows']);
                    $widget['at'] = $acted ? $t : $start + 0.3;
                    $t = $acted ? $t + 0.25 * $rows + 0.4 : $t;
                    break;

                case 'stats':
                    $widget['at'] = $reveal(0.9);
                    break;

                case 'note':
                case 'badge':
                    $widget['at'] = $reveal(0.7);
                    break;

                case 'track':
                    $items = count((array) $widget['items']);
                    $widget['at'] = $acted ? $t : $start + 0.3;
                    $t = $widget['at'] + 0.9 * $items + 0.3;
                    $acted = true;
                    break;

                case 'upload':
                    $widget['at'] = $reveal(0.3);
                    $clicked = $click($id);
                    $widget['progress_at'] = self::range($clicked + 0.2, $clicked + 1.6);
                    $widget['done_at'] = $clicked + 1.7;
                    $t = $clicked + 2.2;
                    break;

                case 'sheet':
                    $widget['at'] = $acted ? $t : $start + 0.3;
                    $widget['stamp_at'] = $widget['at'] + 1.4;
                    $t = $widget['at'] + 2.2;
                    break;

                default:
                    throw new InvalidArgumentException("نوع جزء ناشناخته: {$type}");
            }

            $widget['at'] = round((float) $widget['at'], 2);
            $out[] = $widget;
        }

        return [$out, $t];
    }

    /** بازه زمانی برای data-show و مانند آن. */
    public static function range(float $from, ?float $to = null): string
    {
        return $to === null ? self::n($from) : self::n($from).' '.self::n($to);
    }

    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /** @return list<array{index: int, label: string}> صحنه‌هایی که دکمه فصل دارند */
    public function chapters(): array
    {
        $chapters = [];

        foreach ($this->scenes as $index => $scene) {
            if (($scene['chapter'] ?? null) !== null) {
                $chapters[] = ['index' => $index, 'label' => (string) $scene['chapter']];
            }
        }

        return $chapters;
    }
}
