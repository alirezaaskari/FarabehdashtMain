{{--
    کلید حالت تاریک — فقط در چیدمان‌های میزکار و ابزار.
    انتخاب کاربر در localStorage می‌ماند و با تنظیم سیستم هم هماهنگ است.
--}}

<button type="button"
        data-theme-toggle
        aria-label="تغییر حالت روشن و تاریک"
        {{ $attributes->merge(['class' => 'inline-flex h-touch w-touch items-center justify-center rounded-md text-ink hover:bg-surface-2']) }}>
    <span class="hidden dark:inline" data-theme-icon="dark"><x-icon name="sun" :size="19" /></span>
    <span class="dark:hidden" data-theme-icon="light"><x-icon name="moon" :size="19" /></span>
</button>
