<x-layouts.public title="مرکز ابزارها"
                  description="ابزارهای محاسباتی بهداشت حرفه‌ای: استرس گرمایی، صدا، عوامل شیمیایی، روشنایی و تهویه."
                  active="tools">

    <div class="mx-auto max-w-5xl px-6 py-10 md:px-14">

        <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-ink">مرکز ابزارها</h1>
                <p class="mt-2 max-w-2xl text-muted">
                    هر ابزار رابطه‌اش، منبعش و نسخه‌اش را نشان می‌دهد. محاسبه روی سرور انجام
                    می‌شود و به جاوااسکریپت نیاز ندارد.
                </p>
            </div>

            <x-button href="{{ route('tools.advisor') }}" variant="secondary" icon="compass">
                نمی‌دانم کدام ابزار
            </x-button>
        </header>

        @if ($groups === [])
            <x-empty-state class="mt-10"
                           title="هنوز ابزاری در دسترس نیست"
                           description="ابزارها پس از بازبینی علمی منتشر می‌شوند. کمی بعد دوباره سر بزنید." />
        @endif

        @foreach ($groups as $group)
            <section class="mt-10" aria-labelledby="group-{{ $group['category']->value }}">
                <div class="flex items-center gap-2.5">
                    <span class="text-primary"><x-icon :name="$group['category']->icon()" :size="20" /></span>
                    <h2 id="group-{{ $group['category']->value }}" class="text-xl font-extrabold text-ink">
                        {{ $group['category']->label() }}
                    </h2>
                </div>

                <p class="mt-1 text-sm text-muted">{{ $group['category']->description() }}</p>

                <ul class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($group['tools'] as $tool)
                        <li>
                            <a href="{{ route('tools.show', $tool->slug()) }}"
                               class="block h-full rounded-xl border border-line bg-surface p-5 no-underline
                                      hover:border-primary hover:no-underline">
                                <span class="block text-base font-extrabold text-ink">{{ $tool->definition->title }}</span>
                                <span class="mt-1.5 block text-sm text-muted">{{ $tool->definition->summary }}</span>

                                <span class="mt-4 flex flex-wrap items-center gap-2">
                                    <x-badge tone="neutral">
                                        نسخه <span data-numeric>{{ $tool->version() }}</span>
                                    </x-badge>
                                    <span class="text-xs text-muted" dir="ltr" data-numeric>
                                        {{ $tool->formula->reference()->title }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <x-disclaimer class="mt-12" size="md">
            خروجی این ابزارها محاسبه عددی است و تشخیص پزشکی، تأیید ایمنی یا انطباق قانونی
            محسوب نمی‌شود. تفسیر نتیجه بر عهده کارشناس است.
        </x-disclaimer>

    </div>

</x-layouts.public>
