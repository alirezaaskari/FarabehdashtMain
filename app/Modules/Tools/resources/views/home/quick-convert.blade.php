{{--
    محاسبه سریع غلظت صفحه اصلی: ppm ↔ mg/m³.

    بدون جاوااسکریپت یک فرم معمولی است که به صفحه ابزار کامل می‌رود و نتیجه
    را آن‌جا نشان می‌دهد. با جاوااسکریپت (`quick-convert.js`) هنگام تایپ نتیجه
    را از `tools.preview` می‌گیرد؛ فرمول فقط در موتور محاسبه است و این قالب
    هیچ عددی حساب نمی‌کند. `$directions` و `$substances` را composer ماژول
    ابزارها می‌دهد؛ ابزار خاموش یعنی جهتش نیست و هر دو خاموش یعنی فرم نیست.
--}}

@use('App\Support\PersianNumber')

@if ($directions !== [] && Route::has('tools.calculate') && Route::has('tools.preview'))
    @php
        $first = $directions[0];
        $defaults = $first->definition->defaults;
        $config = array_map(static fn ($tool): array => [
            'action' => route('tools.calculate', $tool->slug()).'#result',
            'preview' => route('tools.preview', $tool->slug()),
            'tool' => route('tools.show', $tool->slug()),
            'unit' => $tool->formula->inputs()['concentration']->unit->symbol(),
            'label' => $tool->definition->title,
        ], $directions);
    @endphp

    <section aria-labelledby="quick-convert-title" class="border-b border-line bg-surface-2 px-6 py-8 md:px-gutter md:py-10">
        <form method="POST" action="{{ $config[0]['action'] }}" data-quick-convert
              data-directions='@json($config)'
              class="grid gap-6 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)_minmax(0,17rem)] lg:items-start lg:gap-10">
            @csrf

            @foreach ($defaults as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <div>
                <h2 id="quick-convert-title" class="text-h3 text-ink">محاسبه سریع غلظت</h2>
                <p class="mt-1.5 text-note text-muted">بدون ورود، با همان موتور محاسبه ابزار کامل.</p>

                @if (count($config) > 1)
                    {{-- جهت تبدیل فقط با جاوااسکریپت معنا دارد؛ بدون آن پنهان می‌ماند. --}}
                    <fieldset data-direction-picker hidden class="mt-4">
                        <legend class="sr-only">جهت تبدیل</legend>
                        <div class="grid grid-cols-2 gap-1 rounded-lg bg-line-soft p-1">
                            @foreach ($config as $index => $direction)
                                <label class="flex min-h-touch cursor-pointer items-center justify-center rounded-md text-label font-bold text-muted
                                              has-[:checked]:bg-surface has-[:checked]:text-ink has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-focus">
                                    <input type="radio" name="direction" value="{{ $index }}" class="sr-only" @checked($index === 0)>
                                    <span data-numeric>{{ str_replace(['تبدیل ', ' به '], ['', ' → '], $direction['label']) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            </div>

            <div>
                @if ($substances !== [])
                    <ul class="flex list-none flex-wrap items-center gap-2 ps-0" aria-label="ماده‌های پرکاربرد">
                        @foreach ($substances as $substance)
                            <li>
                                {{-- بدون جاوااسکریپت پیوندی است به ابزار با وزن مولکولی پرشده. --}}
                                <a href="{{ route('tools.show', [$first->slug(), 'molecular_weight' => $substance['molecular_weight']]) }}"
                                   data-molecular-weight="{{ $substance['molecular_weight'] }}"
                                   class="inline-flex min-h-touch items-center rounded-full border border-line bg-surface px-4 text-label font-semibold
                                          text-body no-underline hover:border-primary-line hover:no-underline data-active:border-primary
                                          data-active:bg-primary-soft data-active:text-primary-deep">
                                    {{ $substance['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <x-field name="concentration" id="quick-concentration" :numeric="true" required
                             :label="'غلظت ('.$config[0]['unit'].')'" />
                    <x-field name="molecular_weight" id="quick-molecular-weight" label="جرم مولکولی (g/mol)" :numeric="true" required />
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <output for="quick-concentration quick-molecular-weight" aria-live="polite" data-result
                        class="flex min-h-field-lg items-baseline justify-between gap-3 rounded-lg bg-primary px-5 py-3.5 text-on-primary">
                    <span class="text-label text-primary-soft">نتیجه</span>
                    <span class="text-h2" data-numeric data-result-value>—</span>
                </output>
                <x-button type="submit" variant="secondary" :block="true">باز کردن در ابزار کامل</x-button>
            </div>

            <p class="text-note text-muted lg:col-span-3">
                در دمای @fa($defaults['temperature'] ?? 25) درجه و فشار {{ PersianNumber::decimal((float) ($defaults['pressure'] ?? 101.325), 3) }} کیلوپاسکال؛
                برای دما و فشار محل و ذخیره نتیجه، ابزار کامل را باز کن. این خروجی جایگزین قضاوت کارشناسی نیست و ادعای انطباق قانونی قطعی ندارد.
            </p>
        </form>
    </section>
@endif
