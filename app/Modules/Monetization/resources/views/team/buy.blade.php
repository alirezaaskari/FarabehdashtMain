<x-layouts.public title="خرید اشتراک تیم" description="اشتراک حرفه‌ای برای چند نفر با یک صورتحساب." active="pro">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['اشتراک حرفه‌ای', route('monetization.plans')], ['خرید برای تیم', null]]" />
    </x-slot:breadcrumb>

    <x-page-header art="team-buy" :title="$team?->isCurrent() ? 'تمدید یا تغییر اشتراک تیم' : 'اشتراک حرفه‌ای برای تیم'"
                   lede="برای هر نفر یک صندلی می‌خرید؛ خودتان یکی از صندلی‌ها هستید. اعضا با شماره موبایل دعوت می‌شوند و هر کدام حساب و داده خودشان را دارند." />

    <x-page-help topic="team-buy" class="mt-5" />

    <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <x-card size="lg">
            @if ($team?->isCurrent())
                <x-alert tone="info" class="mb-6">
                    تیم «{{ $team->name }}» تا {{ \App\Support\JalaliDate::long($team->ends_at) }} فعال است و @fa($team->seat_count) صندلی دارد.
                    دوره تازه از پایان همین دوره شروع می‌شود و تعداد صندلی تازه همان لحظه پرداخت اثر می‌کند.
                </x-alert>
            @endif

            {{-- گام ۱: قیمت با همین فرم دوباره حساب می‌شود؛ بی‌اسکریپت کار می‌کند. --}}
            <form method="GET" action="{{ route('monetization.team.buy') }}" class="flex flex-col gap-5">
                <x-field name="name" label="نام تیم" :value="$name" required hint="مثلاً نام شرکت یا واحد HSE؛ در دعوت اعضا دیده می‌شود." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="seats" label="تعداد صندلی" type="number" :value="$seats" numeric inputmode="numeric" required
                             :hint="'کمینه '.\App\Support\PersianNumber::format($floor).'، بیشینه '.\App\Support\PersianNumber::format($max)"
                             min="{{ $floor }}" max="{{ $max }}" />
                    <fieldset>
                        <legend class="mb-2 block text-label font-semibold text-ink">دوره</legend>
                        @foreach ($cycles as $option)
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="radio" name="cycle" value="{{ $option->value }}" @checked($cycle === $option) class="size-5 shrink-0 accent-primary">
                                {{ $option->label() }}
                            </label>
                        @endforeach
                    </fieldset>
                </div>
                <div><x-button type="submit" variant="secondary" icon="calculator">محاسبه مبلغ</x-button></div>
            </form>
        </x-card>

        <aside class="flex flex-col gap-5">
            <x-card title="مبلغ" heading="text-h4">
                <dl class="flex flex-col gap-2 text-copy">
                    <div class="flex justify-between gap-3"><dt class="text-muted">هر صندلی در ماه</dt><dd class="text-ink">{{ $unit->format() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">صندلی</dt><dd class="text-ink">@fa($seats)</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">ماه صورتحساب</dt><dd class="text-ink">@fa($billedMonths){{ $cycle->value === 'yearly' ? ' (۱۲ ماه دسترسی)' : '' }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-line pt-2"><dt class="font-semibold text-ink">جمع</dt><dd class="text-h4 text-ink">{{ $total->format() }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('monetization.team.checkout') }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="name" value="{{ $name }}">
                    <input type="hidden" name="seats" value="{{ $seats }}">
                    <input type="hidden" name="cycle" value="{{ $cycle->value }}">
                    @foreach (['name', 'seats', 'cycle', 'payment'] as $key)
                        @error($key)
                            <p class="mb-3 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    @endforeach
                    @if ($name === '')
                        <p class="mb-3 text-note text-muted">اول نام تیم را بنویسید و «محاسبه مبلغ» را بزنید.</p>
                    @endif
                    <x-payment-method :total="$total" class="mb-4" />
                    <x-button type="submit" variant="primary" block :disabled="$name === ''">پرداخت {{ $total->format() }}</x-button>
                </form>
            </x-card>

            <x-card title="پله‌های قیمت" heading="text-h4">
                <ul class="flex list-none flex-col gap-2 ps-0 text-copy text-body">
                    @foreach ($tiers as $tier)
                        <li>از @fa($tier['from']) صندلی: {{ $tier['unit']->format() }} برای هر نفر در ماه</li>
                    @endforeach
                    <li>سالانه: دوازده ماه دسترسی با مبلغ ده ماه</li>
                </ul>
            </x-card>
        </aside>
    </div>

    <x-disclaimer class="mt-10">
        صاحب تیم فقط صندلی می‌دهد و پس می‌گیرد؛ به محاسبه، پروژه یا گزارش اعضا دسترسی ندارد. اشتراک فقط دسترسی به ابزارهای سایت است و گواهی یا مدرک رسمی نیست.
    </x-disclaimer>

</x-layouts.public>
