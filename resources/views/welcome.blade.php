{{-- صفحه موقت خانه. صفحه اصلی واقعی در بخش ۹ با بلوک‌های محتوایی ساخته می‌شود. --}}

<x-layouts.public title="میزکار متخصص بهداشت حرفه‌ای"
                  description="دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی، بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی بهداشت حرفه‌ای و ایمنی کار."
                  :padded="false">

    <section class="border-b border-line bg-surface px-6 py-14 md:px-gutter md:py-16">
        <x-badge tone="primary" icon="check">بهداشت حرفه‌ای و ایمنی کار</x-badge>

        <h1 class="mt-4.5 text-4xl text-ink md:text-hero">
            یاد بگیر، محاسبه کن،<br>مستند بساز.
        </h1>

        <p class="mt-5 max-w-[32.5rem] text-lg leading-8 text-muted">
            میزکار فارسی متخصص بهداشت حرفه‌ای: دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی،
            بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی — همه در یک حساب.
        </p>

        <div class="mt-8 flex flex-wrap gap-3.5">
            <x-button :href="route('tools.index')" variant="primary" size="lg">شروع با ابزارها</x-button>
            <x-button href="#" variant="secondary" size="lg">ورود به دانشنامه</x-button>
        </div>
    </section>

    <section class="border-b border-line bg-surface px-6 py-6 md:px-gutter">
        <ul class="flex list-none flex-col gap-4 ps-0 md:flex-row md:gap-12">
            @foreach ([
                ['check', 'هر محتوا بازبین علمی و تاریخ بازبینی دارد'],
                ['book', 'هر فرمول با منبع و نسخه مشخص'],
                ['alert', 'بدون ادعای تشخیص پزشکی یا انطباق قطعی'],
            ] as [$icon, $text])
                <li class="flex items-center gap-2.5 text-sm font-semibold text-body">
                    <span class="text-primary"><x-icon :name="$icon" :size="19" /></span>
                    {{ $text }}
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.public>
