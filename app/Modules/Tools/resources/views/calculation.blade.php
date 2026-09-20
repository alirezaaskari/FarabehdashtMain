<x-layouts.workspace :title="$calculation->label ?? 'محاسبه ذخیره‌شده'"
                     :heading="$calculation->label ?? ($tool?->definition->title ?? $calculation->tool_slug)"
                     :lede="'ثبت‌شده در '.\App\Support\JalaliDate::longWithTime($calculation->created_at)"
                     active="tools"
                     nav="calculations">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['محاسبات ذخیره‌شده', route('tools.calculations.index')],
            [$calculation->label ?? $calculation->tool_slug, null],
        ]" />
    </x-slot:breadcrumb>

    <x-slot:actions>
        <x-button :href="route('tools.calculations.print', $calculation->uuid)"
                  variant="secondary" icon="print" data-print="hide">
            نسخه چاپی
        </x-button>
    </x-slot:actions>

    @if ($reproducible)
        <x-alert tone="success" title="بازتولید شد">
            اجرای دوباره با نسخه فرمول همین ردیف، دقیقاً همین عددها را داد.
        </x-alert>
    @else
        <x-alert tone="error" title="بازتولید نشد">
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
            <x-tools::result :rows="$rows" :notes="$calculation->notes" />
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


    <x-disclaimer class="mt-6">
    این خروجی ادعای تشخیص پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارد و
    جایگزین اندازه‌گیری استاندارد و قضاوت کارشناسی نیست.
    </x-disclaimer>

</x-layouts.workspace>
