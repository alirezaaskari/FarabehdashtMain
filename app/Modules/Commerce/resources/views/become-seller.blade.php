@php
    $start = auth()->check()
        ? (Route::has('identity.profiles') ? route('identity.profiles') : null)
        : (Route::has('login') ? route('login') : null);

    $steps = [
        ['وارد شوید', 'با شماره موبایل وارد فرابهداشت شوید؛ حساب جدا برای فروش لازم نیست.'],
        ['نقش بگیرید', 'در میزکار، «نقش‌ها و پروفایل‌ها»، نقش فروشنده (برای فایل) یا مدرس (برای دوره) را درخواست کنید. مدیر درخواست را بررسی و فعال می‌کند.'],
        ['محتوا را بفرستید', 'فایل یا دوره را با قیمت دلخواه بارگذاری کنید و برای بررسی بفرستید. پس از تأیید مدیر در فروشگاه یا فهرست دوره‌ها منتشر می‌شود.'],
        ['سهمتان جمع می‌شود', 'از هر فروش، سهم شما همان لحظه در دفتر کل به «مانده قابل‌تسویه» اضافه می‌شود.'],
        ['تسویه بگیرید', 'در «تسویه» شبای به نام خودتان را ثبت کنید و وقتی مانده به حد نصاب رسید، درخواست واریز بدهید.'],
    ];
@endphp

<x-layouts.public title="فروشنده شوید"
                  description="فایل، قالب و دوره تخصصی بهداشت حرفه‌ای را در فرابهداشت بفروشید؛ بررسی پیش از انتشار، سهم شفاف و تسویه هفتگی."
                  :canonical="route('commerce.sell')">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['فروشنده شوید', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="فروشنده شوید"
                   lede="فرم، چک‌لیست، قالب گزارش یا دوره آموزشی خودتان را به همکاران بهداشت حرفه‌ای بفروشید. هر محتوا پیش از انتشار بررسی می‌شود و سهم شما هفته‌ای یک بار واریز می‌شود.">
        @if ($start)
            <x-slot:actions>
                <x-button :href="$start" variant="primary" icon="forward">{{ auth()->check() ? 'درخواست نقش فروشنده' : 'ورود و شروع' }}</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-page-help topic="sell" class="mt-5" />

    <section aria-labelledby="what-heading" class="mt-10">
        <h2 id="what-heading" class="text-h2 text-ink">چه چیزی می‌فروشید</h2>

        <div class="mt-5 grid gap-6 md:grid-cols-2">
            <x-card title="فایل و قالب تخصصی" :level="3">
                <p class="text-copy text-muted">
                    فرم مجوز کار، چک‌لیست بازرسی، قالب ارزیابی ریسک و هر فایل آماده‌ای که کار همکاران را کوتاه کند.
                    به‌روزرسانی فایل به دست خریداران قبلی هم می‌رسد.
                </p>
                <p class="mt-4 text-label text-ink">
                    سهم شما: <span class="font-semibold">@fa(100 - $shopRate)٪</span> از هر فروش ·
                    کمیسیون پلتفرم: @fa($shopRate)٪
                </p>
            </x-card>

            @if (Route::has('courses.index'))
                <x-card title="دوره آموزشی" :level="3">
                    <p class="text-copy text-muted">
                        دوره ویدئویی با جلسه و آزمون. دانشجو پس از قبولی گواهی تکمیل دوره می‌گیرد؛ این گواهی مدرک رسمی نیست
                        و نباید چنین معرفی شود.
                    </p>
                    <p class="mt-4 text-label text-ink">
                        سهم شما: <span class="font-semibold">@fa(100 - $courseRate)٪</span> از هر ثبت‌نام ·
                        کمیسیون پلتفرم: @fa($courseRate)٪
                    </p>
                </x-card>
            @endif
        </div>
    </section>

    <section aria-labelledby="steps-heading" class="mt-12">
        <h2 id="steps-heading" class="text-h2 text-ink">در پنج قدم</h2>

        <ol class="mt-5 grid list-none gap-4 ps-0 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as [$title, $body])
                <li class="flex gap-4 rounded-xl border border-line bg-surface px-5 py-5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-soft text-h4 text-on-primary-soft" aria-hidden="true">@fa($loop->iteration)</span>
                    <div>
                        <h3 class="text-h4 text-ink"><span class="sr-only">قدم @fa($loop->iteration): </span>{{ $title }}</h3>
                        <p class="mt-1.5 text-copy text-muted">{{ $body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <div class="mt-12 grid gap-6 md:grid-cols-2">
        <x-card title="قاعده‌های انتشار" :level="2" tone="muted">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-muted">
                <li>هر محتوا و هر نسخه تازه آن پیش از دیده‌شدن با تأیید مدیر منتشر می‌شود.</li>
                <li>محتوا نباید ادعای تشخیص پزشکی، تأیید ایمنی قطعی یا انطباق قانونی قطعی داشته باشد.</li>
                <li>گواهی دوره، مدرک رسمی یا گواهی سازمان‌های بین‌المللی معرفی نمی‌شود.</li>
                <li>فقط محتوایی را بفروشید که حق فروشش با خودتان است.</li>
            </ul>
        </x-card>

        <x-card title="تسویه" :level="2" tone="muted">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-muted">
                <li>کمترین مبلغ هر درخواست تسویه {{ $minimum->format() }} است.</li>
                <li>واریز هفته‌ای یک بار به شبای به نام خود شما انجام می‌شود و شماره پیگیری بانک را در اعلان‌ها می‌بینید.</li>
                <li>شبا رمزنگاری‌شده نگه‌داری می‌شود و جز مدیر مالی کسی آن را کامل نمی‌بیند.</li>
                <li>اگر خریداری پولش را پس بگیرد، سهم آن فروش از مانده شما کم می‌شود.</li>
            </ul>
        </x-card>
    </div>

    @if ($start)
        <div class="mt-12 flex flex-col items-start gap-3 rounded-xl bg-primary px-6 py-8 md:px-9">
            <p class="text-h3 text-on-primary">آماده‌اید؟</p>
            <p class="text-copy text-on-primary">درخواست نقش چند دقیقه طول می‌کشد؛ پس از تأیید، پنل فروشنده در ستون کناری میزکارتان باز می‌شود.</p>
            <x-button :href="$start" variant="on-dark">{{ auth()->check() ? 'درخواست نقش فروشنده' : 'ورود و شروع' }}</x-button>
        </div>
    @endif

</x-layouts.public>
