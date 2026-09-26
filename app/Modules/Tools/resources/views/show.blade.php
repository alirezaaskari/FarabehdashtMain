@php
    use App\Support\JalaliDate;
    use Farabehdasht\CalcEngine\Calculation;

    $reference = $tool->formula->reference();
    // ذخیره ناموفق هم همین صفحه را می‌کشد و حالت میدانی نمی‌شناسد.
    $field ??= false;
    $points ??= [];
@endphp

<x-layouts.public :seo="$seo" active="tools">

    @unless ($field)
    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['ابزارها', route('tools.index')],
            [$tool->definition->category->label(), route('tools.index').'#group-'.$tool->definition->category->value],
            [$tool->definition->title, null],
        ]" />
    </x-slot:breadcrumb>
    @endunless

    {{-- حالت میدانی (`?field=1`): فقط فرم و نتیجه، با فیلدهای درشت برای کار در
         کارگاه. در نشانی است تا نشانک گوشی مستقیم همین حالت را باز کند. --}}
    @if ($field)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-h2 text-ink">{{ $tool->definition->title }}</h1>
            <x-button :href="route('tools.show', $tool->slug())" variant="secondary" size="sm">خروج از حالت میدانی</x-button>
        </div>
    @else
    <x-page-header :title="$tool->definition->title" :lede="$tool->definition->summary" size="display">
        <x-slot:meta>
            <span class="text-note font-semibold text-muted">
                نسخه فرمول: {{ $tool->displayVersion() }}
            </span>

            <span class="text-note font-semibold text-muted">
                منبع: <span dir="ltr" data-numeric>{{ $reference->title }}</span>
            </span>

            <span class="text-note font-semibold text-muted">
                آخرین بازبینی:
                {{ $tool->reviewedAt ? JalaliDate::short($tool->reviewedAt) : 'ثبت نشده' }}
            </span>

            @if ($tool->versionPinned)
                <x-badge tone="caution" icon="lock">نسخه سنجاق‌شده توسط مدیر</x-badge>
            @endif
        </x-slot:meta>

        <x-slot:actions>
            <x-button :href="route('tools.show', [$tool->slug(), 'field' => 1])" variant="secondary" icon="calculator">
                حالت میدانی
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($tool->definition->purpose)
        <x-page-help topic="tool" title="این ابزار به چه کار می‌آید؟" class="mt-5" :help="[
            'purpose' => $tool->definition->purpose,
            'uses' => $tool->definition->uses,
            'example' => $tool->definition->example,
        ]" />
    @endif
    @endif

    @if ($alternative && $tool->definition->variant && $alternative->definition->variant)
        <nav aria-label="نوع محیط" class="mt-5 inline-flex rounded-md bg-surface-2 p-1">
            <span aria-current="page"
                  class="inline-flex min-h-touch items-center rounded-sm bg-surface px-4 text-label font-semibold text-ink shadow-xs">
                {{ $tool->definition->variant }}
            </span>
            <a href="{{ route('tools.show', $field ? [$alternative->slug(), 'field' => 1] : $alternative->slug()) }}"
               class="inline-flex min-h-touch items-center rounded-sm px-4 text-label font-semibold text-muted no-underline
                      hover:text-ink hover:no-underline">
                {{ $alternative->definition->variant }}
            </a>
        </nav>
    @endif

    {{-- فرم به #result می‌فرستد تا روی موبایل پاسخ جلوی چشم باشد، نه زیر
         ورودی‌ها. اگر محاسبه رد شود، همین هشدار خطا مقصد پرش است. --}}
    @if ($fieldErrors !== [])
        <x-alert tone="error" class="mt-6 scroll-mt-4" title="محاسبه انجام نشد" id="result">
            مقدارهای مشخص‌شده را اصلاح کنید و دوباره بفرستید. هیچ ورودی‌ای به‌صورت خودکار
            صفر در نظر گرفته نمی‌شود.
        </x-alert>
    @endif

    {{-- ورودی‌ها در یک ستون؛ نتیجه، فرمول و تفسیر به همین ترتیب در ستون دیگر.
         روی موبایل همین ترتیب زیر هم می‌آید. --}}
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.25fr_1fr]" @if ($field) data-field-mode @endif>

        <x-card size="lg" title="ورودی‌ها" class="lg:col-start-1 lg:row-start-1">
            <form method="POST" action="{{ route('tools.calculate', $field ? [$tool->slug(), 'field' => 1] : $tool->slug()) }}#result"
                  class="flex flex-col gap-5">
                @csrf

                <x-tools::measurement-form :tool="$tool" :submitted="$submitted" :fieldErrors="$fieldErrors" :sources="! $field" />

                <x-button type="submit" variant="primary" size="lg" icon="calculator" block>
                    محاسبه کن
                </x-button>
            </form>

            @if ($tool->definition->window !== null)
                <x-alert tone="info" class="mt-5">
                    این ابزار برای پنجره @fa((int) $tool->definition->window) دقیقه‌ای در نظر گرفته شده است.
                    اگر مجموع مدت‌ها چیز دیگری باشد، نتیجه همچنان محاسبه می‌شود ولی دیگر
                    مواجهه کوتاه‌مدت نیست.
                </x-alert>
            @endif
        </x-card>

        <div @if ($fieldErrors === []) id="result" @endif
             class="flex scroll-mt-4 flex-col gap-6 lg:col-start-2 lg:row-start-1" aria-live="polite">
            @if ($calculation === null)
                <x-empty-state icon="calculator"
                               title="هنوز نتیجه‌ای نیست"
                               description="داده اندازه‌گیری را وارد کنید و «محاسبه کن» را بزنید.">
                    @if ($tool->definition->example)
                        <x-slot:action>
                            <p class="text-note text-muted">
                                <span class="font-semibold text-ink">نمونه:</span>
                                {{ \App\Support\Help\HelpText::render($tool->definition->example) }}
                            </p>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            @else
                <x-tools::result :rows="$rows" :notes="$calculation->notes" />
            @endif

            @unless ($field)
            {{-- رابطه درست زیر عدد: کاربر اول نتیجه را می‌بیند و بلافاصله می‌خواهد
                 بداند از کجا آمده. پیش از محاسبه هم نشان می‌دهد چه چیزی اجرا می‌شود. --}}
            <div class="rounded-lg border border-line bg-surface-2 px-5 py-4">
                <h3 class="text-note font-semibold text-muted">فرمول به‌کاررفته</h3>

                <p class="mt-2 text-lede font-semibold text-ink" dir="ltr" data-numeric>
                    {{ $reference->relation }}
                </p>

                <p class="mt-3 text-note text-muted">
                    مرجع: <span dir="ltr" data-numeric>{{ $reference->title }}</span>
                    — {{ $reference->publisher }}، @fa($reference->year)
                    · نسخه رابطه در فرابهداشت:
                    <span dir="ltr" data-numeric>{{ $tool->formula->id().'@'.$tool->version() }}</span>
                </p>
            </div>
            @endunless

            {{-- نقطه‌های این جلسه: چند اندازه‌گیری پشت هم، کنار هم. فقط در نشست
                 مرورگر می‌ماند؛ برای نگه‌داشتن هر کدام، «ذخیره محاسبه» هست. --}}
            @if (count($points) > 1)
                @php
                    $pointInputs = array_filter(
                        $tool->formula->inputs(),
                        static fn ($input, $key): bool => ! $input->list && isset($points[0]->inputs[$key]),
                        ARRAY_FILTER_USE_BOTH,
                    );
                @endphp

                <x-card title="نقطه‌های این جلسه">
                    {{-- جدول فشرده، نه x-data-table: چند عدد کوتاه باید روی گوشی بدون اسکرول
                         افقی کنار هم دیده شوند. --}}
                    <table class="mt-4 w-full border-collapse text-label">
                        <caption class="sr-only">محاسبه‌های همین جلسه با این ابزار، به ترتیب</caption>
                        <thead class="border-b border-line">
                            <tr>
                                <th scope="col" class="py-2.5 pe-2 text-start text-note font-semibold text-muted">#</th>
                                @foreach ($pointInputs as $input)
                                    <th scope="col" class="px-2 py-2.5 text-start text-note font-semibold text-muted">{{ $input->label }}</th>
                                @endforeach
                                <th scope="col" class="py-2.5 ps-2 text-start text-note font-semibold text-ink">نتیجه</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($points as $point)
                                <tr class="border-b border-line-soft last:border-0">
                                    <td class="py-2.5 pe-2 text-muted">@fa($loop->iteration)</td>
                                    @foreach (array_keys($pointInputs) as $key)
                                        <td class="px-2 py-2.5 whitespace-nowrap"><bdi dir="ltr" data-numeric>{{ $point->inputs[$key] ?? '—' }}</bdi></td>
                                    @endforeach
                                    <td class="py-2.5 ps-2 font-semibold whitespace-nowrap text-ink">
                                        <bdi dir="ltr" data-numeric>{{ $point->value }} {{ $point->unit }}</bdi>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <form method="POST" action="{{ route('tools.points.clear', $field ? [$tool->slug(), 'field' => 1] : $tool->slug()) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="secondary" size="sm">پاک کردن این فهرست</x-button>
                    </form>
                    <p class="mt-2 text-note text-muted">این فهرست فقط تا پایان همین جلسه مرورگر می‌ماند.</p>
                </x-card>
            @endif

            @if ($calculation !== null)

                <x-card title="تفسیر">
                    {{-- متن تفسیر مال گروه ابزار است؛ پیش‌تر متن گرما زیر نتیجه صدا هم می‌آمد. --}}
                    <p class="text-copy text-body">
                        {{ $tool->definition->category->interpretation() }}
                        این ابزار مقدار را محاسبه می‌کند و قضاوت نهایی بر عهده کارشناس است.
                    </p>

                    {{-- «بعدش چه؟»: کاربر پس از عدد رها نمی‌شود. --}}
                    @if ($mentionedIn !== [] && ! $field)
                        <div class="mt-4 border-t border-line-soft pt-4">
                            <h3 class="text-note font-semibold text-muted">برای تفسیر بیشتر</h3>
                            <ul class="mt-1 list-none ps-0">
                                @foreach (array_slice($mentionedIn, 0, 2) as $link)
                                    <li>
                                        <a href="{{ $link->url }}" class="inline-flex min-h-touch items-center gap-2 text-label font-semibold">
                                            <x-icon name="book" :size="16" />
                                            {{ $link->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-col gap-2.5">
                        @auth
                            <form method="POST" action="{{ route('tools.calculations.store', $tool->slug()) }}"
                                  class="flex flex-col gap-3">
                                @csrf

                                @foreach ($submitted as $key => $value)
                                    @if (is_array($value))
                                        @foreach ($value as $item)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach

                                <x-field name="label"
                                         label="نام این محاسبه (اختیاری)"
                                         hint="مثلاً «ایستگاه ۳ — شیفت صبح». برای پیدا کردن دوباره‌اش در سابقه." />

                                <x-button type="submit" variant="primary" icon="save" block>
                                    ذخیره محاسبه در میزکار
                                </x-button>
                            </form>

                            <p class="text-note text-muted">
                                محاسبه ذخیره‌شده تغییرناپذیر است و نسخه رابطه همین لحظه را نگه می‌دارد،
                                تا بعداً دقیقاً همین عدد بازتولید شود.
                            </p>
                        @else
                            <x-permission-notice
                                title="برای ذخیره این محاسبه باید وارد شوید"
                                description="محاسبه بدون حساب هم کار می‌کند؛ فقط نگه‌داشتنش در سابقه به حساب نیاز دارد." />
                        @endauth
                    </div>
                </x-card>
            @endif

            {{-- متن سلب ادعا از خود موتور محاسبات می‌آید، نه از قالب: یک منبع، یک متن. --}}
            <x-disclaimer>
                {{ Calculation::DISCLAIMER }}
                جایگزین اندازه‌گیری استاندارد و قضاوت کارشناسی هم نیست.
            </x-disclaimer>
        </div>


    </div>

    @unless ($field)
    <section aria-labelledby="about-tool" class="mt-12 border-t border-line-strong pt-8">
        <h2 id="about-tool" class="text-h2 text-ink">درباره این ابزار</h2>
        <div class="mt-6 grid gap-8 md:grid-cols-3">
            <div>
                <h3 class="text-h4 text-ink">کاربرد</h3>
                <p class="mt-2.5 text-copy text-body">{{ $tool->definition->summary }}</p>
            </div>

            <div>
                <h3 class="text-h4 text-ink">محدودیت‌ها</h3>
                <ul class="mt-2.5 flex list-none flex-col gap-2 ps-0">
                    @foreach ($tool->formula->limitations() as $line)
                        <li class="text-copy text-body">{{ $line }}</li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="text-h4 text-ink">منابع</h3>
                <p class="mt-2.5 text-copy text-body">
                    <span dir="ltr" data-numeric>{{ $reference->title }}</span>
                    — {{ $reference->publisher }}، @fa($reference->year)
                </p>

                @if ($reference->note !== '')
                    <p class="mt-2 text-note text-muted">{{ $reference->note }}</p>
                @endif
            </div>
        </div>
    </section>

    <x-mentioned-in :items="$mentionedIn" class="mt-8" />
    @endunless

</x-layouts.public>
