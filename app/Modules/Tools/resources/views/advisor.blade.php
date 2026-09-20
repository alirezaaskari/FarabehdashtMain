<x-layouts.public title="دستیار انتخاب ابزار"
                  description="نمی‌دانید کدام ابزار؟ موقعیت میدانی خود را پیدا کنید."
                  active="tools">

    <div class="mx-auto max-w-3xl px-6 py-10 md:px-14">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm">
            <a href="{{ route('tools.index') }}"
               class="inline-flex h-touch items-center text-primary">مرکز ابزارها</a>
            <span class="text-muted"> / دستیار انتخاب</span>
        </nav>

        <h1 class="text-3xl font-extrabold text-ink">کدام ابزار به کارم می‌آید؟</h1>

        <p class="mt-2 text-muted">
            موقعیتی را که در آن هستید پیدا کنید. هر مورد می‌گوید چرا همان ابزار درست است،
            نه فقط اینکه کدام است.
        </p>

        @if ($suggestions === [])
            <x-empty-state class="mt-8"
                           title="فعلاً پیشنهادی نیست"
                           description="ابزارها پس از بازبینی علمی منتشر می‌شوند." />
        @endif

        <ul class="mt-8 flex flex-col gap-3">
            @foreach ($suggestions as $suggestion)
                <li>
                    <a href="{{ route('tools.show', $suggestion['tool']->slug()) }}"
                       class="flex flex-col gap-3 rounded-xl border border-line bg-surface p-5 no-underline
                              hover:border-primary hover:no-underline md:flex-row md:items-center">
                        <span class="grow">
                            <span class="block text-base font-bold text-ink">«{{ $suggestion['situation'] }}»</span>
                            <span class="mt-1.5 block text-sm text-muted">{{ $suggestion['reason'] }}</span>
                        </span>

                        <span class="flex shrink-0 items-center gap-2 text-sm font-bold text-primary">
                            {{ $suggestion['tool']->definition->title }}
                            <x-icon name="forward" :size="16" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-disclaimer class="mt-10" size="sm">
            این فهرست راهنماست و جای تصمیم کارشناس را نمی‌گیرد. انتخاب روش اندازه‌گیری و
            دامنه پایش بر عهده کارشناس بهداشت حرفه‌ای است.
        </x-disclaimer>

    </div>

</x-layouts.public>
