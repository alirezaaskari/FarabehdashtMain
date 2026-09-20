<x-layouts.public title="سابقه محاسبه‌های من" noindex active="tools">

    <div class="mx-auto max-w-3xl px-6 py-10 md:px-14">

        <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">سابقه محاسبه‌های من</h1>
                <p class="mt-1.5 text-sm text-muted">
                    هر ردیف نسخه فرمول لحظه ثبتش را نگه می‌دارد و تغییرناپذیر است.
                </p>
            </div>

            <x-button :href="route('tools.index')" variant="secondary" icon="calculator">
                محاسبه تازه
            </x-button>
        </header>

        @if ($calculations->isEmpty())
            <x-empty-state class="mt-8"
                           icon="calculator"
                           title="هنوز محاسبه‌ای ذخیره نکرده‌اید"
                           description="پس از هر محاسبه می‌توانید آن را با یک نام در سابقه نگه دارید." />
        @else
            <ul class="mt-8 flex flex-col gap-3">
                @foreach ($calculations as $item)
                    <li>
                        <a href="{{ route('tools.calculations.show', $item->uuid) }}"
                           class="flex items-center justify-between gap-4 rounded-xl border border-line bg-surface
                                  p-4 no-underline hover:border-primary hover:no-underline">
                            <span>
                                <span class="block font-bold text-ink">
                                    {{ $item->label ?? $item->tool_slug }}
                                </span>
                                <span class="mt-1 block text-xs text-muted">
                                    {{ \App\Support\JalaliDate::short($item->created_at) }} ·
                                    <span dir="ltr" data-numeric>{{ $item->formula_id.'@'.$item->formula_version }}</span>
                                </span>
                            </span>

                            <x-icon name="forward" :size="16" class="shrink-0 text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $calculations->links() }}</div>
        @endif

    </div>

</x-layouts.public>
