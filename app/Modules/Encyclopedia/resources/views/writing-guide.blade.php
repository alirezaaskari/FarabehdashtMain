{{--
    راهنمای عمومی نویسندگی دانشنامه: چه کسی می‌نویسد، چه چیزی خوب است، مسیر
    از پیش‌نویس تا انتشار، و قالب متن و منبع.

    قالب متن و منبع همان راهنمای کنار فرم نویسنده است (`writing.guide`) و
    دوباره نوشته نمی‌شود؛ هر تغییری در آن این‌جا هم دیده می‌شود.
--}}

@php
    $good = [
        ['یک موضوع، یک نوشته', 'یک پرسش مشخص را کامل جواب بده؛ مثلاً «اندازه‌گیری صدا با دزیمتر» به‌جای «همه‌چیز درباره صدا».'],
        ['از تجربه میدانی بنویس', 'خطاهای رایج، نکته‌های کالیبراسیون و آنچه در کتاب نیست، برای همکاران از همه ارزشمندتر است.'],
        ['منبع نسخه‌دار بیاور', 'استاندارد، راهنما یا مقاله با ویرایش و سال؛ تا خواننده بداند عدد از کجا آمده و کی کهنه می‌شود.'],
        ['عدد و واحد لاتین بماند', 'مقدار اندازه‌گیری، شماره CAS و کد استاندارد لاتین نوشته می‌شوند تا در متن راست‌چین وارونه نشوند.'],
    ];

    $steps = [
        ['پروفایل نویسنده را فعال کن', 'در میزکار از «نقش‌ها و پروفایل‌ها» پروفایل «نویسنده دانشنامه» را درخواست کن تا مدیر تأییدش کند.'],
        ['پیش‌نویس بنویس', 'بخش «نوشته‌های دانشنامه» در میزکار باز می‌شود؛ تا نفرستاده‌ای هر چند بار خواستی ذخیره کن.'],
        ['برای بازبینی بفرست', 'مدیر متن را می‌خواند، بازبین علمی تعیین می‌کند و منتشرش می‌کند یا با یادداشت برمی‌گرداند.'],
        ['با نام خودت منتشر می‌شود', 'نوشته با نام تو، تاریخ بازبینی و پیوند به صفحه نویسنده‌ات در دانشنامه می‌آید.'],
    ];
@endphp

<x-layouts.public title="راهنمای نوشتن در دانشنامه"
                  description="چطور نویسنده دانشنامه فرابهداشت شوید، چه چیزی بنویسید و نوشته‌تان از پیش‌نویس تا انتشار چه مسیری طی می‌کند."
                  :canonical="route('encyclopedia.writing-guide')"
                  active="encyclopedia">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['دانشنامه', route('encyclopedia.index')], ['راهنمای نوشتن', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="راهنمای نوشتن در دانشنامه"
                   lede="هر کارشناس بهداشت حرفه‌ای می‌تواند نویسنده دانشنامه شود. نوشته‌ات پیش از انتشار بازبینی علمی می‌شود و با نام خودت منتشر می‌شود." />

    <section aria-labelledby="guide-steps" class="mt-12">
        <h2 id="guide-steps" class="text-h2 text-ink">از پیش‌نویس تا انتشار</h2>
        <ol class="mt-6 grid list-none gap-8 ps-0 md:grid-cols-2 md:gap-6 lg:grid-cols-4">
            @foreach ($steps as $index => [$title, $text])
                <li class="border-t-2 border-ink pt-5">
                    <span class="text-note font-semibold text-muted">قدم @fa($index + 1)</span>
                    <h3 class="mt-1 text-h4 text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-note text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>

        <div class="mt-6 flex flex-wrap gap-3">
            @auth
                @can('content.write')
                    @if (Route::has('encyclopedia.writing.create'))
                        <x-button :href="route('encyclopedia.writing.create')" size="lg" class="max-sm:w-full">نوشتن پیش‌نویس تازه</x-button>
                    @endif
                @else
                    @if (Route::has('identity.profiles'))
                        <x-button :href="route('identity.profiles')" size="lg" class="max-sm:w-full">درخواست پروفایل نویسنده</x-button>
                    @endif
                @endcan
            @else
                @if (Route::has('login'))
                    <x-button :href="route('login')" size="lg" class="max-sm:w-full">ورود و شروع نوشتن</x-button>
                @endif
            @endauth
        </div>
    </section>

    <section aria-labelledby="guide-good" class="mt-12">
        <h2 id="guide-good" class="text-h2 text-ink">نوشته خوب دانشنامه</h2>
        <ul class="mt-6 grid list-none gap-x-10 gap-y-6 ps-0 md:grid-cols-2">
            @foreach ($good as [$title, $text])
                <li class="flex gap-3 border-t border-line pt-5">
                    <span class="mt-1 text-primary"><x-icon name="check" :size="18" /></span>
                    <span>
                        <span class="block text-h4 text-ink">{{ $title }}</span>
                        <span class="mt-1 block text-note text-muted">{{ $text }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="guide-format" class="mt-12 rounded-xl border border-line bg-surface p-6 md:p-8">
        <h2 id="guide-format" class="text-h2 text-ink">قالب متن و منبع</h2>
        <p class="mt-2 mb-5 text-copy text-muted">همان راهنمایی که کنار فرم نوشتن در میزکار می‌بینی.</p>
        @include('encyclopedia::writing.guide')
    </section>

    <x-disclaimer class="mt-10">
        نوشته‌های دانشنامه جنبه آموزشی دارند و ادعای تشخیص پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی
        نمی‌کنند. انتشار با نام نویسنده به‌معنای گواهی رسمی یا مجوز حرفه‌ای نیست.
    </x-disclaimer>
</x-layouts.public>
