<x-layouts.public title="دستیار انتخاب ابزار"
                  description="نمی‌دانید کدام ابزار؟ موقعیت میدانی خود را پیدا کنید."
                  :canonical="route('tools.advisor')"
                  active="tools">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['ابزارها', route('tools.index')],
            ['دستیار انتخاب', null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header title="دستیار انتخاب ابزار"
                   lede="موقعیتی را که در آن هستید پیدا کنید. هر مورد می‌گوید چرا همان ابزار
                         درست است، نه فقط اینکه کدام است." />

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">

        <x-card size="lg" title="در چه موقعیتی هستید؟">
            @if ($suggestions === [])
                <x-empty-state title="فعلاً پیشنهادی نیست"
                               description="ابزارها پس از بازبینی علمی منتشر می‌شوند." />
            @else
                <ul class="grid list-none gap-3 ps-0 md:grid-cols-2">
                    @foreach ($suggestions as $suggestion)
                        <li>
                            <a href="{{ route('tools.show', $suggestion['tool']->slug()) }}"
                               class="flex h-full flex-col rounded-lg border border-line bg-surface px-5 py-4.5
                                      text-start no-underline hover:border-primary hover:bg-primary-soft
                                      hover:no-underline">
                                <span class="text-h4 text-ink">{{ $suggestion['situation'] }}</span>
                                <span class="mt-1.5 block text-note text-muted">{{ $suggestion['reason'] }}</span>

                                <span class="mt-3 flex items-center gap-1.5 text-note font-bold text-primary">
                                    {{ $suggestion['tool']->definition->title }}
                                    <x-icon name="forward" :size="14" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6">
                <x-button :href="route('tools.index')" variant="secondary">رد کردن دستیار</x-button>
            </div>
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card title="چرا این دستیار؟">
                <p class="text-copy text-body">
                    کاربر تازه‌کار وارد فهرست ابزارها می‌شود و گم می‌شود. این مسیر او را
                    مستقیم به ابزار درست می‌رساند و دلیلش را هم می‌گوید.
                </p>
            </x-card>

            <x-disclaimer>
                این فهرست راهنماست و جای تصمیم کارشناس را نمی‌گیرد. انتخاب روش اندازه‌گیری و
                دامنه پایش بر عهده کارشناس بهداشت حرفه‌ای است.
            </x-disclaimer>
        </div>

    </div>

</x-layouts.public>
