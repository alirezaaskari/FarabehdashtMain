@php use App\Modules\Tools\Domain\Enums\ToolAvailability; @endphp

<x-layouts.public title="مرکز ابزارهای تخصصی"
                  description="ابزارهای محاسباتی بهداشت حرفه‌ای: استرس گرمایی، صدا، عوامل شیمیایی، روشنایی و تهویه."
                  active="tools">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['ابزارها', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="مرکز ابزارهای تخصصی"
                   lede="هر ابزار: توضیح کاربرد، ورودی با واحد، اعتبارسنجی، فرمول، منبع علمی، نسخه فرمول،
                         تاریخ بازبینی، تفسیر محدود و غیرپزشکی، ذخیره و چاپ.">
        <x-slot:actions>
            <x-button :href="route('tools.advisor')" variant="secondary" icon="compass">
                نمی‌دانم کدام ابزار
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($groups === [])
        <x-empty-state class="mt-8"
                       title="هنوز ابزاری در دسترس نیست"
                       description="ابزارها پس از بازبینی علمی منتشر می‌شوند. کمی بعد دوباره سر بزنید." />
    @endif

    @foreach ($groups as $group)
        <section aria-labelledby="group-{{ $group['category']->value }}">
            <h2 id="group-{{ $group['category']->value }}"
                class="mt-8 mb-3.5 text-xl font-extrabold text-ink">
                {{ $group['category']->label() }}
            </h2>

            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($group['tools'] as $tool)
                    <li>
                        <a href="{{ route('tools.show', $tool->slug()) }}"
                           class="block h-full rounded-xl border border-line bg-surface px-5.5 py-5
                                  no-underline hover:border-primary hover:no-underline">
                            <span class="flex items-start justify-between gap-3">
                                <span class="text-h4 text-ink">{{ $tool->definition->title }}</span>

                                <x-badge :tone="$tool->availability === ToolAvailability::Available ? 'primary' : 'caution'"
                                         class="shrink-0">
                                    نسخه <span data-numeric>{{ $tool->version() }}</span>
                                </x-badge>
                            </span>

                            <span class="mt-2.5 block text-note text-muted">{{ $tool->definition->summary }}</span>

                            <span class="mt-3 block text-xs text-muted">
                                <span dir="ltr" data-numeric>{{ $tool->formula->reference()->title }}</span>
                                · {{ $tool->availability->label() }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    <x-disclaimer class="mt-8">
        {{ \Farabehdasht\CalcEngine\Calculation::DISCLAIMER }}
        تفسیر نتیجه بر عهده کارشناس است.
    </x-disclaimer>

</x-layouts.public>
