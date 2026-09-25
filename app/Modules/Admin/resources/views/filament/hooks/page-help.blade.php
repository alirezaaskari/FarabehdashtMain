{{--
    راهنمای صفحه پنل: «این صفحه به چه کار می‌آید؟». متن از config/help.php
    (کلید panel، با نام مسیر صفحه) می‌آید؛ صفحه‌ای که راهنما ندارد چیزی نشان نمی‌دهد.
--}}
<details data-page-help="{{ $route }}"
         class="group rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white
                    [&::-webkit-details-marker]:hidden">
        <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
        <span class="grow">این صفحه به چه کار می‌آید؟</span>
        <x-filament::icon icon="heroicon-m-chevron-down" class="h-5 w-5 text-gray-400 transition group-open:rotate-180" />
    </summary>

    <div class="mt-3 flex flex-col gap-3 text-sm leading-7 text-gray-700 dark:text-gray-300">
        <p>{{ \App\Support\Help\HelpText::render($help['purpose']) }}</p>

        @if (! empty($help['uses']))
            <div>
                <p class="font-semibold text-gray-950 dark:text-white">کاربردها</p>
                <ul class="mt-1 list-disc ps-5">
                    @foreach ($help['uses'] as $use)
                        <li>{{ \App\Support\Help\HelpText::render($use) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($help['example']))
            <div class="rounded-lg border-s-4 border-primary-500 bg-gray-50 p-3 dark:bg-white/5">
                <p class="font-semibold text-gray-950 dark:text-white">مثال</p>
                <p class="mt-1">{{ \App\Support\Help\HelpText::render($help['example']) }}</p>
            </div>
        @endif
    </div>
</details>
