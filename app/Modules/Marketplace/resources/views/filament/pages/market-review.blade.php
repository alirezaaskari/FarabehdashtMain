<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            هر پروژه بازار و هر اصلاح آن پیش از انتشار این‌جا می‌آید. پیوست‌ها خصوصی‌اند و فقط برای سنجش شما و مجری پس از قرارداد.
            پروژه‌ای که شماره تماس، ایمیل یا نشانی پیام‌رسان در متنش دارد، ادعای مجوز یا تأیید رسمی می‌خواهد، یا بودجه‌اش با کار نمی‌خواند،
            با یادداشت برگردانید.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">در انتظار (@fa(count($rows)))</h2>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">موردی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section wire:key="project-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $row['title'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="approve({{ $row['id'] }})">تأیید و انتشار</x-filament::button>
                </div>

                <dl class="grid gap-3 text-sm">
                    @foreach ($row['fields'] as $field)
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-gray-700 dark:text-gray-200">{{ $field['value'] !== '' ? $field['value'] : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">پیوست‌های خصوصی (@fa(count($row['files'])))</p>
                    <ul class="mt-1 text-sm">
                        @forelse ($row['files'] as $file)
                            <li><a href="{{ $file['url'] }}" class="underline">{{ $file['name'] }}</a></li>
                        @empty
                            <li class="text-gray-500 dark:text-gray-400">پیوستی ندارد.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="چه چیزی باید اصلاح شود؛ کارفرما همین را می‌بیند"
                                               wire:model="notes.{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">برگرداندن</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
