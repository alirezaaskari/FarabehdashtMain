{{--
    محاسبه سریع غلظت صفحه اصلی: ppm ↔ mg/m³. در قهرمان صفحه اصلی کنار
    جست‌وجو می‌نشیند و خودش نمونه زنده محصول است.

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

    <section aria-labelledby="quick-convert-title"
             class="rounded-xl border border-line bg-surface">
        <form method="POST" action="{{ $config[0]['action'] }}" data-quick-convert
              data-directions='@json($config)'>
            @csrf

            @foreach ($defaults as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 md:px-6">
                <h2 id="quick-convert-title" class="text-h4 text-ink">محاسبه سریع غلظت</h2>

                @if (count($config) > 1)
                    {{-- جهت تبدیل فقط با جاوااسکریپت معنا دارد؛ بدون آن پنهان می‌ماند. --}}
                    <fieldset data-direction-picker hidden>
                        <legend class="sr-only">جهت تبدیل</legend>
                        <div class="flex gap-1 rounded-md bg-surface-2 p-1">
                            @foreach ($config as $index => $direction)
                                <label class="flex min-h-touch cursor-pointer items-center justify-center rounded-sm px-3 text-note font-semibold text-muted
                                              has-[:checked]:bg-surface has-[:checked]:text-ink has-[:checked]:shadow-xs
                                              has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-focus">
                                    <input type="radio" name="direction" value="{{ $index }}" class="sr-only" @checked($index === 0)>
                                    <span data-numeric>{{ str_replace(['تبدیل ', ' به '], ['', ' → '], $direction['label']) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            </div>

            <div class="space-y-5 px-5 py-5 md:px-6">
                @if ($substances !== [])
                    <div>
                        <p id="quick-substances" class="text-note text-muted">ماده پرکاربرد</p>
                        <ul class="mt-2 flex list-none flex-wrap gap-2 ps-0" aria-labelledby="quick-substances">
                            @foreach ($substances as $substance)
                                <li>
                                    {{-- بدون جاوااسکریپت پیوندی است به ابزار با وزن مولکولی پرشده. --}}
                                    <a href="{{ route('tools.show', [$first->slug(), 'molecular_weight' => $substance['molecular_weight']]) }}"
                                       data-molecular-weight="{{ $substance['molecular_weight'] }}"
                                       class="inline-flex min-h-touch items-center rounded-md border border-line px-3.5 text-label font-medium
                                              text-body no-underline hover:border-line-strong hover:text-ink hover:no-underline
                                              data-active:border-primary data-active:bg-primary-soft data-active:text-primary-deep">
                                        {{ $substance['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    {{-- مثال پرشده تا نتیجه از همان اول زنده باشد؛ ماده اولِ فهرست. --}}
                    <x-field name="concentration" id="quick-concentration" :numeric="true" required value="50"
                             :label="'غلظت ('.$config[0]['unit'].')'" />
                    <x-field name="molecular_weight" id="quick-molecular-weight" label="جرم مولکولی (g/mol)" :numeric="true" required
                             :value="$substances[1]['molecular_weight'] ?? $substances[0]['molecular_weight'] ?? null" />
                </div>

                <output for="quick-concentration quick-molecular-weight" aria-live="polite" data-result
                        class="flex min-h-field-lg items-baseline justify-between gap-3 border-t border-line pt-4">
                    <span class="text-label text-muted">نتیجه</span>
                    <span class="text-h1 text-ink" data-numeric data-result-value>—</span>
                </output>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-2 px-5 py-4 md:px-6 rounded-b-xl">
                <p class="text-note text-muted">
                    @fa($defaults['temperature'] ?? 25) درجه، {{ PersianNumber::decimal((float) ($defaults['pressure'] ?? 101.325), 3) }} کیلوپاسکال
                </p>
                <x-button type="submit" variant="secondary" size="sm">باز کردن در ابزار کامل</x-button>
            </div>
        </form>
    </section>
@endif
