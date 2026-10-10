<x-layouts.workspace art="tools-calculation" :title="$calculation->label ?? 'محاسبه ذخیره‌شده'"
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
        <dl class="flex flex-col gap-2 text-label">
            @foreach ($inputs as $row)
                <div class="flex items-baseline justify-between gap-4 border-b border-line py-1.5 last:border-0">
                    <dt class="text-muted">{{ $row->label }}</dt>
                    @if ($row->numeric)
                        <dd class="font-semibold text-ink" dir="ltr" data-numeric>
                            {{ $row->value }}@if ($row->unit) <span class="text-muted">{{ $row->unit }}</span>@endif
                        </dd>
                    @else
                        <dd class="text-end font-semibold text-ink">{{ $row->value }}</dd>
                    @endif
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
                <dl class="flex flex-col gap-2 text-label">
                    @foreach ($rows as $row)
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="text-muted">{{ $row->label }}</dt>
                            <dd class="font-semibold text-ink" dir="ltr" data-numeric>{{ $row->value }} {{ $row->unit }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        @endif
    </div>

    @if ($compareDecision !== null)
        <x-card class="mt-6" title="مقایسه با ارزیابی‌های دیگر" :level="2" data-print="hide">
            @if ($peers === [])
                <p class="text-copy text-muted">
                    ارزیابی دیگری با این روش ذخیره نکرده‌اید. ایستگاه دیگری را ارزیابی کنید، یا همین
                    ایستگاه را پس از اصلاح، و با نامی جدا ذخیره کنید تا این‌جا کنار هم ببینیدشان.
                </p>
            @else
                <form method="GET" action="{{ route('tools.calculations.compare') }}" class="flex flex-col gap-3">
                    <input type="hidden" name="c[]" value="{{ $calculation->uuid }}">

                    <fieldset>
                        <legend class="mb-2 text-label text-muted">
                            ارزیابی‌هایی که می‌خواهید کنار این یکی ببینید (تا @fa(\App\Modules\Tools\Services\AssessmentComparer::MAX - 1) تا).
                            با یک انتخاب، مقایسه پیش و پس از اصلاح می‌شود و ارزیابی قدیمی‌تر مبنا است.
                        </legend>

                        @foreach ($peers as $peer)
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="checkbox" name="c[]" value="{{ $peer->uuid }}" class="size-5 shrink-0 accent-primary">
                                <span class="grow">{{ $peer->label ?? 'بی‌نام' }}</span>
                                <span class="text-note text-muted">{{ \App\Support\JalaliDate::short($peer->created_at) }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    @if ($compareDecision->denied())
                        <p class="text-note text-muted">مقایسه ارزیابی‌ها ویژه اشتراک حرفه‌ای است.</p>
                    @endif

                    <div>
                        <x-button type="submit" variant="secondary" icon="forward">مقایسه</x-button>
                    </div>
                </form>
            @endif
        </x-card>
    @endif

    <p class="mt-6 text-note text-muted" dir="ltr" data-numeric>
        {{ $calculation->formula_id.'@'.$calculation->formula_version }} · {{ $calculation->uuid }}
    </p>

    {{-- حذف برگشت ندارد؛ دکمه واقعی پشت یک باز‌شونده است تا با یک لمس اشتباه نرود. --}}
    <details class="mt-6 rounded-xl border border-line bg-surface px-5" data-print="hide">
        <summary class="flex min-h-touch cursor-pointer items-center text-label font-semibold text-danger">
            حذف این محاسبه
        </summary>

        <form method="POST" action="{{ route('tools.calculations.destroy', $calculation->uuid) }}" class="pb-5">
            @csrf
            @method('DELETE')
            <p class="mb-4 text-label text-muted">
                محاسبه از فهرست شما و از سقف پلن حذف می‌شود و برنمی‌گردد. اگر در پروژه یا
                گزارشی به کار رفته باشد، داده‌اش فقط برای همان‌جا نگه داشته می‌شود.
            </p>
            <x-button type="submit" variant="danger" size="sm">بله، حذف شود</x-button>
        </form>
    </details>

    <x-disclaimer class="mt-6">
    این خروجی ادعای تشخیص پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارد و
    جایگزین اندازه‌گیری استاندارد و قضاوت کارشناسی نیست.
    </x-disclaimer>

</x-layouts.workspace>
