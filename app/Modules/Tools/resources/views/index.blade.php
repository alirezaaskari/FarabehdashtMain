@php use App\Modules\Tools\Domain\Enums\ToolAvailability; @endphp

<x-layouts.public title="مرکز ابزارهای تخصصی"
                  description="ابزارهای محاسباتی بهداشت حرفه‌ای: استرس گرمایی، صدا، عوامل شیمیایی، روشنایی و تهویه."
                  :canonical="route('tools.index')"
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

    <x-page-help topic="tools" class="mt-5" />

    @if ($groups === [])
        <x-empty-state class="mt-8"
                       title="هنوز ابزاری در دسترس نیست"
                       description="ابزارها پس از بازبینی علمی منتشر می‌شوند. کمی بعد دوباره سر بزنید." />
    @endif

    @if ($recent !== [])
        <section aria-labelledby="recent-tools" class="mt-8">
            <h2 id="recent-tools" class="text-h4 text-ink">اخیراً استفاده‌شده</h2>

            <ul class="mt-3 flex list-none flex-wrap gap-3 ps-0">
                @foreach ($recent as $tool)
                    <li>
                        <a href="{{ route('tools.show', $tool->slug()) }}"
                           class="inline-flex min-h-touch items-center gap-2 rounded-md border border-line bg-surface px-3.5
                                  text-label font-medium text-ink no-underline hover:border-line-strong hover:no-underline">
                            <x-icon :name="$tool->definition->category->icon()" :size="16" class="text-muted" />
                            {{ $tool->definition->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- فهرست فنی، نه شبکه کارت: عامل زیان‌آور در یک ستون، ابزارهایش ردیف‌به‌ردیف در ستون دیگر. --}}
    <div class="mt-10 border-t border-line-strong">
        @foreach ($groups as $group)
            <section aria-labelledby="group-{{ $group['category']->value }}"
                     class="grid gap-4 border-b border-line py-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10">
                <div class="flex items-center gap-2.5 self-start lg:sticky lg:top-6">
                    <span class="text-muted"><x-icon :name="$group['category']->icon()" :size="18" /></span>
                    <h2 id="group-{{ $group['category']->value }}" class="text-h3 text-ink">{{ $group['category']->label() }}</h2>
                    <span class="text-note text-muted">@fa(count($group['tools']))</span>
                </div>

                <ul class="list-none ps-0">
                    @foreach ($group['tools'] as $tool)
                        @php($reviewed = $tool->availability === ToolAvailability::Available)
                        <li class="border-b border-line-soft first:border-t-0 last:border-b-0">
                            <a href="{{ route('tools.show', $tool->slug()) }}"
                               class="group -mx-3 grid gap-x-8 gap-y-1 rounded-md px-3 py-4 no-underline hover:bg-surface-2 hover:no-underline
                                      md:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_8.5rem]">
                                <span>
                                    <span class="block text-h4 text-ink group-hover:text-primary">{{ $tool->definition->title }}</span>
                                    <span class="mt-1 block text-note text-muted">{{ $tool->definition->summary }}</span>
                                </span>
                                <span class="text-note text-muted md:pt-1" data-numeric>{{ $tool->formula->reference()->title }}</span>
                                <span @class(['text-note md:pt-1', 'text-primary' => $reviewed, 'text-muted' => ! $reviewed])>
                                    نسخه {{ $tool->displayVersion() }} · {{ $tool->availability->label() }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <x-disclaimer class="mt-10">
        {{ \Farabehdasht\CalcEngine\Calculation::DISCLAIMER }}
        تفسیر نتیجه بر عهده کارشناس است.
    </x-disclaimer>

</x-layouts.public>
