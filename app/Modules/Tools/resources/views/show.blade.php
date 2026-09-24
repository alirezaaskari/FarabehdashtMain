@php
    use App\Support\JalaliDate;
    use Farabehdasht\CalcEngine\Calculation;

    $reference = $tool->formula->reference();
@endphp

<x-layouts.public :seo="$seo" active="tools">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['ابزارها', route('tools.index')],
            [$tool->definition->category->label(), route('tools.index').'#group-'.$tool->definition->category->value],
            [$tool->definition->title, null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header :title="$tool->definition->title" :lede="$tool->definition->summary" size="display">
        <x-slot:meta>
            <span class="text-note font-semibold text-muted">
                نسخه فرمول: <span data-numeric>{{ $tool->version() }}</span>
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
    </x-page-header>

    {{-- فرم به #result می‌فرستد تا روی موبایل پاسخ جلوی چشم باشد، نه زیر
         ورودی‌ها. اگر محاسبه رد شود، همین هشدار خطا مقصد پرش است. --}}
    @if ($fieldErrors !== [])
        <x-alert tone="error" class="mt-6 scroll-mt-4" title="محاسبه انجام نشد" id="result">
            مقدارهای مشخص‌شده را اصلاح کنید و دوباره بفرستید. هیچ ورودی‌ای به‌صورت خودکار
            صفر در نظر گرفته نمی‌شود.
        </x-alert>
    @endif

    {{-- روی موبایل ترتیب ورودی‌ها، نتیجه، فرمول است؛ روی دسکتاپ فرمول زیر
         ورودی‌ها در ستون راست و نتیجه در ستون چپ. --}}
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.25fr_1fr]">

        <x-card size="lg" title="ورودی‌ها" class="lg:col-start-1 lg:row-start-1">
            <form method="POST" action="{{ route('tools.calculate', $tool->slug()) }}#result"
                  class="flex flex-col gap-5">
                @csrf

                <x-tools::measurement-form :tool="$tool" :submitted="$submitted" :fieldErrors="$fieldErrors" />

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

        {{-- جعبه رابطه: کاربر باید ببیند چه چیزی اجرا می‌شود. روی موبایل بعد از
             نتیجه می‌آید تا پاسخ زیر رابطه گم نشود. --}}
        <div class="rounded-xl border border-line bg-surface-2 px-6 py-5.5 lg:col-start-1 lg:row-start-2">
            <h3 class="text-copy font-bold text-ink">فرمول به‌کاررفته</h3>

            <p class="mt-3 text-lede font-bold text-primary-deep" dir="ltr" data-numeric>
                {{ $reference->relation }}
            </p>

            <p class="mt-3 text-note text-muted">
                مرجع: <span dir="ltr" data-numeric>{{ $reference->title }}</span>
                — {{ $reference->publisher }}، @fa($reference->year)
                · نسخه رابطه در فرابهداشت:
                <span dir="ltr" data-numeric>{{ $tool->formula->id().'@'.$tool->version() }}</span>
            </p>
        </div>

        <div @if ($fieldErrors === []) id="result" @endif
             class="flex scroll-mt-4 flex-col gap-6 lg:col-start-2 lg:row-span-2 lg:row-start-1" aria-live="polite">
            @if ($calculation === null)
                <x-empty-state icon="calculator"
                               title="هنوز نتیجه‌ای نیست"
                               description="داده اندازه‌گیری را وارد کنید و «محاسبه کن» را بزنید." />
            @else
                <x-tools::result :rows="$rows" :notes="$calculation->notes" />

                <x-card title="تفسیر">
                    <p class="text-copy text-body">
                        مقایسه این عدد با حد مرجع، به بار متابولیکی کار، پوشش لباس کار و برنامه
                        کار–استراحت بستگی دارد. این ابزار مقدار را محاسبه می‌کند و قضاوت نهایی
                        بر عهده کارشناس است.
                    </p>

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

    <x-card size="lg" class="mt-6" title="درباره این ابزار" heading="text-h2">
        <div class="grid gap-7 md:grid-cols-3">
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
    </x-card>

    <x-mentioned-in :items="$mentionedIn" class="mt-8" />

</x-layouts.public>
