<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\SettingsStore;
use App\Modules\Jobs\Events\JobPricingChanged;
use App\Modules\Jobs\Services\JobPricing;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * تغییر عددهای کاریابی از پنل: قیمت و مدت آگهی، «اولین آگهی رایگان»، مهلت
 * ۴۱۰، سقف درخواست روزانه و آستانه آزمون گذرنامه ({@see JobPricing::rules()}).
 * دوره‌های پرداخت‌شده قیمت و مدت خودشان را نگه می‌دارند.
 */
final readonly class UpdateJobPricing
{
    public function __construct(
        private SettingsStore $settings,
        private JobPricing $pricing,
        private Dispatcher $events,
    ) {}

    /** @param  array<string, int>  $values  نام کوتاه قاعده => مقدار تازه */
    public function handle(array $values, int $actorId): void
    {
        $rules = $this->pricing->rules();
        $before = $this->snapshot();
        $after = $before;

        foreach ($values as $name => $value) {
            $rule = $rules[$name] ?? throw new InvalidArgumentException('قاعده ناشناخته: '.$name);

            if ($value < $rule['min'] || $value > $rule['max']) {
                throw new InvalidArgumentException(sprintf('«%s» باید بین %s و %s باشد.', $rule['label'], number_format($rule['min']), number_format($rule['max'])));
            }

            $after[$rule['key']] = $value;
        }

        if ($before === $after) {
            return;
        }

        foreach ($rules as $rule) {
            if ($after[$rule['key']] !== $before[$rule['key']]) {
                $this->settings->set($rule['key'], $after[$rule['key']], JobPricing::GROUP, $rule['label']);
            }
        }

        $this->events->dispatch(new JobPricingChanged($before, $after, $actorId));
    }

    /** @return array<string, int> کلید تنظیم => مقدار فعلی */
    public function snapshot(): array
    {
        $snapshot = [];

        foreach ($this->pricing->rules() as $name => $rule) {
            $snapshot[$rule['key']] = $this->pricing->value($name);
        }

        return $snapshot;
    }
}
