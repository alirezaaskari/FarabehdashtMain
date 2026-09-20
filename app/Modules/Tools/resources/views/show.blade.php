<x-layouts.public :title="$tool->definition->title"
                  :description="$tool->definition->summary"
                  active="tools">

    <div class="mx-auto max-w-4xl px-6 py-10 md:px-14">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm">
            <a href="{{ route('tools.index') }}"
               class="inline-flex h-touch items-center text-primary">مرکز ابزارها</a>
            <span class="text-muted"> / {{ $tool->definition->category->label() }}</span>
        </nav>

        <header>
            <h1 class="text-3xl font-extrabold text-ink">{{ $tool->definition->title }}</h1>
            <p class="mt-2 max-w-2xl text-muted">{{ $tool->definition->summary }}</p>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-badge tone="neutral">
                    نسخه رابطه <span data-numeric>{{ $tool->version() }}</span>
                </x-badge>

                @if ($tool->versionPinned)
                    <x-badge tone="caution" icon="lock">نسخه سنجاق‌شده توسط مدیر</x-badge>
                @endif

                <span class="text-xs text-muted">
                    بازبینی علمی:
                    @if ($tool->reviewedAt)
                        {{ \App\Support\JalaliDate::short($tool->reviewedAt) }}
                    @else
                        ثبت نشده
                    @endif
                </span>
            </div>
        </header>

        @if ($fieldErrors !== [])
            <x-alert tone="error" class="mt-6" title="محاسبه انجام نشد">
                مقدارهای مشخص‌شده را اصلاح کنید و دوباره بفرستید. هیچ ورودی‌ای به‌صورت خودکار
                صفر در نظر گرفته نمی‌شود.
            </x-alert>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">

            <section aria-labelledby="form-heading">
                <h2 id="form-heading" class="text-lg font-extrabold text-ink">داده اندازه‌گیری</h2>

                <form method="POST" action="{{ route('tools.calculate', $tool->slug()) }}"
                      class="mt-4 flex flex-col gap-5">
                    @csrf

                    <x-tools::measurement-form :tool="$tool" :submitted="$submitted" :fieldErrors="$fieldErrors" />

                    <x-button type="submit" variant="primary" size="lg" icon="calculator" block>
                        محاسبه کن
                    </x-button>
                </form>

                @if ($tool->definition->window !== null)
                    <x-alert tone="info" class="mt-4">
                        این ابزار برای پنجره @fa((int) $tool->definition->window) دقیقه‌ای در نظر گرفته شده است.
                        اگر مجموع مدت‌ها چیز دیگری باشد، نتیجه همچنان محاسبه می‌شود ولی دیگر
                        مواجهه کوتاه‌مدت نیست.
                    </x-alert>
                @endif
            </section>

            <section aria-labelledby="result-heading" aria-live="polite">
                <h2 id="result-heading" class="text-lg font-extrabold text-ink">نتیجه</h2>

                <div class="mt-4">
                    @if ($calculation === null)
                        <x-empty-state icon="calculator"
                                       title="هنوز نتیجه‌ای نیست"
                                       description="داده اندازه‌گیری را وارد کنید و «محاسبه کن» را بزنید." />
                    @else
                        <x-tools::result :rows="$rows"
                                                  :formula="$tool->formula"
                                                  :notes="$calculation->notes"
                                                  :disclaimers="$calculation->disclaimers()" />

                        <div class="mt-6">
                            @auth
                                <form method="POST" action="{{ route('tools.calculations.store', $tool->slug()) }}"
                                      class="flex flex-col gap-3 rounded-xl border border-line bg-surface-2 p-4">
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

                                    <x-button type="submit" variant="secondary" icon="save">
                                        ذخیره در سابقه من
                                    </x-button>

                                    <p class="text-xs text-muted">
                                        محاسبه ذخیره‌شده تغییرناپذیر است و نسخه رابطه همین لحظه را نگه می‌دارد،
                                        تا بعداً دقیقاً همین عدد بازتولید شود.
                                    </p>
                                </form>
                            @else
                                <x-permission-notice
                                    title="برای ذخیره این محاسبه باید وارد شوید"
                                    description="محاسبه بدون حساب هم کار می‌کند؛ فقط نگه‌داشتنش در سابقه به حساب نیاز دارد." />
                            @endauth
                        </div>
                    @endif
                </div>
            </section>

        </div>

    </div>

</x-layouts.public>
