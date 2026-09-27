@php
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;

    $pricing = app(App\Modules\Jobs\Services\JobPricing::class);
    $payments = $this->payments();
    $month = $this->lastThirtyDays();
@endphp

<x-filament-panels::page>

    <x-filament::section heading="قیمت و مدت">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            کارفرما برای هر دوره انتشار آگهی این مبلغ را می‌پردازد و آگهی به همین تعداد روز در فهرست می‌ماند. قیمت و مدت هر
            پرداخت روی خودش ثبت می‌شود و تغییر این‌جا آگهی‌های در جریان را جابه‌جا نمی‌کند. ارسال درخواست برای کارجو همیشه رایگان است.
            @if (! $pricing->salesOpen())
                <strong>کلید «ثبت آگهی شغلی» خاموش است</strong>؛ تا روشن شود، همه آگهی‌های تأییدشده رایگان منتشر می‌شوند.
            @endif
        </p>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label for="job-price" class="text-sm font-semibold text-gray-950 dark:text-white">قیمت هر دوره (تومان)</label>
                <x-filament::input.wrapper class="mt-2">
                    <x-filament::input id="job-price" type="text" dir="ltr" data-numeric wire:model="price" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label for="job-days" class="text-sm font-semibold text-gray-950 dark:text-white">مدت اعتبار (روز)</label>
                <x-filament::input.wrapper class="mt-2">
                    <x-filament::input id="job-days" type="text" dir="ltr" data-numeric wire:model="days" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label for="job-gone" class="text-sm font-semibold text-gray-950 dark:text-white">ماندن آگهی منقضی پیش از حذف (روز)</label>
                <x-filament::input.wrapper class="mt-2">
                    <x-filament::input id="job-gone" type="text" dir="ltr" data-numeric wire:model="goneDays" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <label class="mt-4 flex items-center gap-2 text-sm text-gray-950 dark:text-white">
            <x-filament::input.checkbox wire:model="firstFree" />
            اولین آگهی هر کارفرما رایگان است
        </label>

        <div class="mt-4">
            <x-filament::button wire:click="save">ذخیره</x-filament::button>
        </div>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="سی روز اخیر">
        <p class="text-sm text-gray-950 dark:text-white">
            {{ PersianDigits::from($month['count']) }} دوره انتشار، {{ PersianDigits::from($month['free']) }} رایگان، جمعاً {{ $month['revenue']->format() }}
        </p>
    </x-filament::section>

    <x-filament::section heading="تازه‌ترین دوره‌های انتشار">
        @if ($payments->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">هنوز آگهی‌ای منتشر نشده است.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($payments as $payment)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="payment-{{ $payment->id }}">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $payment->posting->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $payment->posting->company->name }} · {{ PersianDigits::from($payment->days) }} روز ·
                                {{ $payment->paid_at ? JalaliDate::long($payment->paid_at) : '—' }}
                            </p>
                        </div>
                        <p class="text-sm text-gray-950 dark:text-white">
                            {{ $payment->is_free ? 'رایگان' : $payment->price()->format().' · '.$payment->payment_source?->label() }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
