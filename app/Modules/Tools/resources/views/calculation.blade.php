<x-layouts.public :title="$calculation->label ?? 'محاسبه ذخیره‌شده'"
                  noindex
                  active="tools">

    <div class="mx-auto max-w-3xl px-6 py-10 md:px-14">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm" data-print="hide">
            <a href="{{ route('tools.calculations.index') }}"
               class="inline-flex h-touch items-center text-primary">سابقه محاسبه‌های من</a>
        </nav>

        <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">
                    {{ $calculation->label ?? ($tool?->definition->title ?? $calculation->tool_slug) }}
                </h1>
                <p class="mt-1.5 text-sm text-muted">
                    ثبت‌شده در {{ \App\Support\JalaliDate::longWithTime($calculation->created_at) }}
                </p>
            </div>

            <x-button :href="route('tools.calculations.print', $calculation->uuid)"
                      variant="secondary" icon="print" data-print="hide">
                نسخه چاپی
            </x-button>
        </header>

        @if ($reproducible)
            <x-alert tone="success" class="mt-6" title="بازتولید شد">
                اجرای دوباره با نسخه فرمول همین ردیف، دقیقاً همین عددها را داد.
            </x-alert>
        @else
            <x-alert tone="error" class="mt-6" title="بازتولید نشد">
                اجرای دوباره با نسخه ثبت‌شده، عدد دیگری داد. تا روشن‌شدن علت، این محاسبه را
                مبنای گزارش قرار ندهید و موضوع را به پشتیبانی اطلاع دهید.
            </x-alert>
        @endif

        <x-card class="mt-6" title="داده اندازه‌گیری" :level="2">
            <dl class="flex flex-col gap-2 text-sm">
                @foreach ($inputs as $row)
                    <div class="flex items-baseline justify-between gap-4 border-b border-line py-1.5 last:border-0">
                        <dt class="text-muted">{{ $row->label }}</dt>
                        <dd class="font-bold text-ink" dir="ltr" data-numeric>
                            {{ $row->value }}@if ($row->unit) <span class="text-muted">{{ $row->unit }}</span>@endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <div class="mt-6">
            @if ($tool !== null)
                <x-tools::result :rows="$rows"
                                          :formula="$tool->formula"
                                          :notes="$calculation->notes"
                                          :disclaimers="$tool->formula->limitations()" />
            @else
                {{-- ابزار از فهرست برداشته شده؛ محاسبه ذخیره‌شده همچنان باید خوانا بماند. --}}
                <x-card title="نتیجه" :level="2">
                    <dl class="flex flex-col gap-2 text-sm">
                        @foreach ($rows as $row)
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-muted">{{ $row->label }}</dt>
                                <dd class="font-bold text-ink" dir="ltr" data-numeric>{{ $row->value }} {{ $row->unit }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endif
        </div>

        <p class="mt-6 text-xs text-muted" dir="ltr" data-numeric>
            {{ $calculation->formula_id.'@'.$calculation->formula_version }} · {{ $calculation->uuid }}
        </p>

    </div>

</x-layouts.public>
