<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            پرسش پس از تأیید به دست مشاوران می‌رسد و اگر عمومی باشد در سایت دیده می‌شود. پاسخ پس از تأیید زیر پرسش
            منتشر می‌شود. پاسخ نباید ادعای تشخیص پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی داشته باشد؛ چنین پاسخی را
            با یادداشت برگردانید. رد یا برگرداندن بدون یادداشت پذیرفته نیست.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">پرسش‌های در انتظار (@fa(count($questions)))</h2>

    @if ($questions === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">پرسشی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($questions as $row)
        <x-filament::section wire:key="q-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            @if ($row['priority'])<x-filament::badge color="warning" class="inline-flex">Pro</x-filament::badge>@endif
                            {{ $row['title'] }}
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="publishQuestion({{ $row['id'] }})">تأیید</x-filament::button>
                </div>

                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['body'] }}</p>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="دلیل رد، که پرسش‌کننده می‌بیند"
                                               wire:model="notes.q{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="rejectQuestion({{ $row['id'] }})">رد</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">پاسخ‌های در انتظار (@fa(count($answers)))</h2>

    @if ($answers === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">پاسخی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($answers as $row)
        <x-filament::section wire:key="a-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            <a href="{{ $row['url'] }}" target="_blank" class="underline">{{ $row['question'] }}</a>
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="publishAnswer({{ $row['id'] }})">انتشار</x-filament::button>
                </div>

                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['body'] }}</p>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="چه چیزی باید اصلاح شود؛ مشاور همین را می‌بیند"
                                               wire:model="notes.a{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="rejectAnswer({{ $row['id'] }})">برگرداندن</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
