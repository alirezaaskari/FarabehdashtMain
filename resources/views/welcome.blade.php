{{-- صفحه موقت خانه. صفحه اصلی واقعی در بخش ۹ با بلوک‌های محتوایی ساخته می‌شود. --}}

<x-layouts.public title="میزکار متخصص بهداشت حرفه‌ای"
                  description="دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی، بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی بهداشت حرفه‌ای و ایمنی کار.">
    <section class="px-6 py-16 md:px-14 md:py-24">
        <x-badge tone="primary" icon="check">بهداشت حرفه‌ای و ایمنی کار</x-badge>

        <h1 class="mt-5 max-w-3xl text-4xl font-extrabold tracking-tight text-ink md:text-5xl">
            یاد بگیر، محاسبه کن، مستند بساز.
        </h1>

        <p class="mt-5 max-w-xl text-lg text-muted">
            میزکار فارسی متخصص بهداشت حرفه‌ای: دانشنامه بازبینی‌شده، ابزارهای محاسباتی با منبع علمی،
            بانک مواد شیمیایی، و فایل‌ها و دوره‌های تخصصی — همه در یک حساب.
        </p>

        <div class="mt-8 flex flex-wrap gap-3.5">
            <x-button href="#" variant="primary" size="lg">شروع با ابزارها</x-button>
            <x-button href="#" variant="secondary" size="lg">ورود به دانشنامه</x-button>
        </div>
    </section>

    <section class="border-t border-line bg-surface px-6 py-6 md:px-14">
        <ul class="flex flex-col gap-4 md:flex-row md:gap-12">
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
