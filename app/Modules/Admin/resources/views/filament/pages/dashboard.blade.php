<x-filament-panels::page>
    {{--
        داشبورد تصمیم‌محور: صف تأیید بالای صفحه است و آمار پایین‌تر می‌آید.
        هر مدیر فقط مواردی را می‌بیند که توانایی تصمیم‌گیری درباره‌شان را دارد؛
        فیلتر در ApprovalQueue انجام شده، نه اینجا.
    --}}

    @if ($roleLabels !== [])
        <p class="fi-ta-text-item-label text-sm text-gray-500 dark:text-gray-400">
            نقش شما: {{ implode('، ', $roleLabels) }}
        </p>
    @endif

    @if ($pending === [])
        <x-filament::section>
            <div class="py-8 text-center">
                <p class="text-base font-semibold text-gray-950 dark:text-white">
                    هیچ چیزی معطل شما نیست.
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    هر موردی که نیاز به تصمیم شما داشته باشد، همین‌جا بالای صفحه می‌آید.
                </p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section :heading="'صف تأیید — '.\App\Support\PersianDigits::from(count($pending)).' مورد'">
            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($pending as $item)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <a href="{{ $item['url'] }}"
                           class="grow text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">
                            {{ $item['title'] }}
                        </a>

                        @if ($item['by'])
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['by'] }}</span>
                        @endif

                        @if ($item['waiting'])
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                از {{ $item['waiting'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

    <x-filament::section heading="یادآوری قواعد">
        <ol class="flex list-decimal flex-col gap-2 ps-5 text-sm text-gray-600 dark:text-gray-300">
            <li>هیچ محتوایی بدون بررسی شما عمومی نمی‌شود.</li>
            <li>هر رد یا درخواست اصلاح، یادداشت دارد و یادداشت برای ارسال‌کننده می‌رود.</li>
            <li>هر تصمیم شما در دفتر رویداد ثبت می‌شود و قابل حذف نیست.</li>
            <li>در حالت «مشاهده به‌عنوان کاربر» هیچ عملیات مالی انجام نمی‌شود.</li>
        </ol>
    </x-filament::section>
</x-filament-panels::page>
