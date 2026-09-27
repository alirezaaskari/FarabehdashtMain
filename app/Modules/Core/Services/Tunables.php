<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\TunableSource;
use App\Modules\Core\Events\TunableChanged;
use App\Support\PersianNumber;
use App\Support\Settings\Tunable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Throwable;

/**
 * قیمت‌ها و مدت‌های پنل «قیمت‌ها و زمان‌ها».
 *
 * کلید تنظیم همان کلید config است. `apply()` پس از بالا آمدن همه ماژول‌ها
 * مقدار ذخیره‌شده را روی config می‌نشاند؛ ماژول‌ها همان `config()` قبلی را
 * می‌خوانند. مقدار config فایل «پیش‌فرض» می‌ماند و در پنل کنار عدد فعلی دیده می‌شود.
 */
final class Tunables
{
    /** @var array<string, Tunable>|null */
    private ?array $definitions = null;

    /** @var array<string, int> مقدار فایل config، پیش از نشاندن عدد مدیر */
    private array $defaults = [];

    /** @param  iterable<TunableSource>  $sources */
    public function __construct(
        private readonly iterable $sources,
        private readonly SettingsRepository $settings,
        private readonly Config $config,
        private readonly Dispatcher $events,
    ) {}

    /** @return array<string, Tunable> */
    public function definitions(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];

            foreach ($this->sources as $source) {
                foreach ($source->tunables() as $tunable) {
                    $this->definitions[$tunable->key] = $tunable;
                    $this->defaults[$tunable->key] ??= (int) $this->config->get($tunable->key);
                }
            }
        }

        return $this->definitions;
    }

    /** @return array<string, list<Tunable>> به ترتیب اعلام ماژول‌ها */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->definitions() as $tunable) {
            $sections[$tunable->section][] = $tunable;
        }

        return $sections;
    }

    public function apply(): void
    {
        try {
            $stored = $this->settings->all();
        } catch (Throwable) {
            // پیش از اولین مهاجرت جدول تنظیمات نیست؛ config فایل کافی است.
            return;
        }

        foreach ($this->definitions() as $key => $tunable) {
            $value = $stored[$key] ?? null;

            if (is_numeric($value) && $tunable->accepts((int) $value)) {
                $this->config->set($key, (int) $value);
            }
        }
    }

    public function current(string $key): int
    {
        return (int) $this->config->get($key);
    }

    public function default(string $key): int
    {
        $this->definitions();

        return $this->defaults[$key] ?? $this->current($key);
    }

    /**
     * ذخیره عددهای تازه؛ همه یا هیچ.
     *
     * @param  array<string, int>  $values
     * @return list<string> کلیدهایی که واقعاً عوض شدند
     */
    public function update(array $values, int $actorId): array
    {
        $definitions = $this->definitions();

        foreach ($values as $key => $value) {
            $tunable = $definitions[$key] ?? throw new InvalidArgumentException('تنظیم ناشناخته: '.$key);

            if (! $tunable->accepts($value)) {
                throw new InvalidArgumentException(sprintf(
                    '«%s» باید بین %s و %s %s باشد.',
                    $tunable->label,
                    PersianNumber::format($tunable->min),
                    PersianNumber::format($tunable->max),
                    $tunable->unit->label(),
                ));
            }
        }

        $changed = [];

        foreach ($values as $key => $value) {
            $before = $this->current($key);

            if ($before === $value) {
                continue;
            }

            $this->settings->set($key, $value, 'tunables', $definitions[$key]->label);
            $this->config->set($key, $value);
            $this->events->dispatch(new TunableChanged($key, $before, $value, $actorId));
            $changed[] = $key;
        }

        return $changed;
    }
}
