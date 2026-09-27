<x-filament-panels::page>

    <x-filament::section>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            هر صفحه شرکت و هر آگهی، و هر ویرایش آن‌ها، پیش از دیده‌شدن این‌جا می‌آید؛ تا تصمیم شما نسخه قبلی روی سایت می‌ماند.
            مدرک ثبت شرکت یا معرفی‌نامه خصوصی است و فقط برای سنجش شماست. آگهی‌ای که کارجو را به پرداخت پول وادار می‌کند،
            ادعای مجوز یا گواهی رسمی دارد یا شماره تماس شخصی در متنش آمده، با یادداشت برگردانید.
        </p>
    </x-filament::section>

    <h2 class="text-lg font-bold text-gray-950 dark:text-white">در انتظار (@fa(count($rows)))</h2>

    @if ($rows === [])
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">موردی در انتظار نیست.</p>
        </x-filament::section>
    @endif

    @foreach ($rows as $row)
        @php $company = $row['kind'] === 'company'; @endphp
        <x-filament::section wire:key="{{ $row['key'] }}">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <x-filament::badge :color="$company ? 'info' : 'gray'" class="mb-1 inline-flex">{{ $company ? 'شرکت' : 'آگهی' }}</x-filament::badge>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">
                            @if ($row['url'])
                                <a href="{{ $row['url'] }}" target="_blank" class="underline">{{ $row['name'] }}</a>
                            @else
                                {{ $row['name'] }}
                            @endif
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['meta'] }}</p>
                    </div>
                    <x-filament::button size="sm" wire:click="{{ $company ? 'approveCompany' : 'approvePosting' }}({{ $row['id'] }})">تأیید</x-filament::button>
                </div>

                <dl class="grid gap-3 text-sm">
                    @foreach ($row['fields'] as $field)
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $field['label'] }}
                                @if ($field['changed'])<x-filament::badge color="warning" class="ms-1 inline-flex">تغییر کرده</x-filament::badge>@endif
                            </dt>
                            <dd class="mt-1 whitespace-pre-line text-gray-700 dark:text-gray-200">{{ $field['value'] !== '' ? $field['value'] : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($company)
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">مدرک‌های خصوصی (@fa(count($row['documents'])))</p>
                        <ul class="mt-1 text-sm">
                            @forelse ($row['documents'] as $document)
                                <li><a href="{{ $document['url'] }}" class="underline">{{ $document['name'] }}</a></li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">مدرکی فرستاده نشده.</li>
                            @endforelse
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="grow">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" placeholder="چه چیزی باید اصلاح شود؛ کارفرما همین را می‌بیند"
                                               wire:model="notes.{{ $row['key'] }}" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button size="sm" color="danger" wire:click="{{ $company ? 'rejectCompany' : 'rejectPosting' }}({{ $row['id'] }})">برگرداندن</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endforeach

</x-filament-panels::page>
