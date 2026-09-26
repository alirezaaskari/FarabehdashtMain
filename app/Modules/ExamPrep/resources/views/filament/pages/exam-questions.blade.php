<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            سؤال‌هایی که مدرسان نوشته‌اند پس از تأیید شما در بسته منتشر می‌شوند. پاسخ درست، روشن بودن متن و درستی
            پیوند مطالعه را بررسی کنید. برگرداندن بدون یادداشت پذیرفته نیست؛ نویسنده یادداشت را می‌بیند.
        </p>
    </x-filament::section>

    @if ($questions === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">سؤالی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($questions as $row)
        <x-filament::section wire:key="q-{{ $row['id'] }}">
            <div class="flex flex-col gap-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="whitespace-pre-line font-semibold text-gray-950 dark:text-white">{{ $row['body'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="publish({{ $row['id'] }})">انتشار</x-filament::button>
                </div>

                <ol class="list-decimal ps-5 text-gray-700 dark:text-gray-200">
                    @foreach ($row['choices'] as $choice)
                        <li @class(['font-semibold text-success-700 dark:text-success-400' => $choice['correct']])>
                            {{ $choice['body'] }}@if ($choice['correct']) (درست)@endif
                        </li>
                    @endforeach
                </ol>

                @if ($row['explanation'])
                    <p class="whitespace-pre-line text-gray-600 dark:text-gray-300">توضیح: {{ $row['explanation'] }}</p>
                @endif
                @if ($row['reference'])
                    <p><a href="{{ url($row['reference']) }}" target="_blank" class="underline">{{ $row['reference'] }}</a></p>
                @endif

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="چه چیزی باید اصلاح شود؛ نویسنده همین را می‌بیند"
                                               wire:model="notes.{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">برگرداندن</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
