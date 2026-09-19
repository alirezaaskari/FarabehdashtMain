@php use App\Support\PersianDigits; @endphp

<x-filament-panels::page>
    <x-filament::section>
        <label for="audit-action" class="block text-sm font-semibold text-gray-950 dark:text-white">
            فیلتر بر اساس نوع کار
        </label>

        <select id="audit-action" wire:model.live="action"
                class="mt-2 block w-full max-w-md rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
            <option value="">همه کارها</option>
            @foreach ($actions as $value)
                <option value="{{ $value }}">{{ $value }}</option>
            @endforeach
        </select>

        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            مجموع: {{ PersianDigits::from($total) }} ردیف
        </p>
    </x-filament::section>

    @if ($rows === [])
        <x-filament::section>
            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                ردیفی با این شرط پیدا نشد.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead class="border-b border-gray-200 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">زمان</th>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">کار</th>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">بازیگر</th>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">موضوع</th>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">تغییر</th>
                            <th class="px-3 py-2 text-start font-semibold whitespace-nowrap">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap align-top">{{ $row['at'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs align-top whitespace-nowrap" dir="ltr">{{ $row['action'] }}</td>
                                <td class="px-3 py-2 align-top whitespace-nowrap">{{ $row['actor'] }}</td>
                                <td class="px-3 py-2 whitespace-nowrap align-top" dir="ltr">{{ $row['subject'] }}</td>
                                <td class="px-3 py-2 align-top">
                                    @if ($row['before'] !== [] || $row['after'] !== [])
                                        <span dir="ltr" class="font-mono text-xs">
                                            {{ json_encode($row['before'], JSON_UNESCAPED_UNICODE) }}
                                            →
                                            {{ json_encode($row['after'], JSON_UNESCAPED_UNICODE) }}
                                        </span>
                                    @elseif ($row['context'] !== [])
                                        <span dir="ltr" class="font-mono text-xs">
                                            {{ json_encode($row['context'], JSON_UNESCAPED_UNICODE) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-top whitespace-nowrap" dir="ltr">{{ $row['ip'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($this->lastPage() > 1)
                <div class="mt-4 flex items-center justify-between text-sm">
                    <button type="button" wire:click="goToPage({{ $page - 1 }})" @disabled($page <= 1)
                            class="rounded-lg px-3 py-2 font-semibold disabled:opacity-40">صفحه قبل</button>

                    <span class="text-gray-500 dark:text-gray-400">
                        صفحه {{ PersianDigits::from($page) }} از {{ PersianDigits::from($this->lastPage()) }}
                    </span>

                    <button type="button" wire:click="goToPage({{ $page + 1 }})" @disabled($page >= $this->lastPage())
                            class="rounded-lg px-3 py-2 font-semibold disabled:opacity-40">صفحه بعد</button>
                </div>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section heading="چرا این ردیف‌ها پاک نمی‌شوند">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            دفتر رویداد فقط افزودنی است. مدل آن در سطح کد ویرایش و حذف را رد می‌کند،
            نه اینکه فقط دکمه‌اش اینجا نباشد. دفتری که بشود دستکاری‌اش کرد، دفتر نیست.
        </p>
    </x-filament::section>
</x-filament-panels::page>
