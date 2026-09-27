<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            خدمت پس از تأیید روی صفحه مشاور قابل خرید است. شرح نباید ادعای مدرک رسمی، تأیید ایمنی قطعی، تشخیص پزشکی
            یا «انطباق قانونی تضمینی» داشته باشد و راه تماس بیرون از سایت (شماره، ایمیل، شبکه اجتماعی) نمی‌پذیرد.
            ویرایش خدمت منتشرشده آن را دوباره به همین صف می‌آورد.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">در انتظار (@fa(count($rows)))</h2>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">خدمتی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        <x-filament::section wire:key="s-{{ $row['id'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            @if ($row['url'])
                                <a href="{{ $row['url'] }}" target="_blank" class="underline">{{ $row['title'] }}</a>
                            @else
                                {{ $row['title'] }}
                            @endif
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="approve({{ $row['id'] }})">تأیید و انتشار</x-filament::button>
                </div>

                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $row['description'] }}</p>

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="چه چیزی باید اصلاح شود؛ مشاور همین را می‌بیند"
                                               wire:model="notes.s{{ $row['id'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="reject({{ $row['id'] }})">برگرداندن</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
