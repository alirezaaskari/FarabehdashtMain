{{--
    فوتر مشترک: چهار ستون پروتوتایپ (نام سایت، محصول، فروشگاه، قوانین) و
    یک نوار پایین.

    هر پیوند با Route::has محافظت شده تا خاموش‌شدن یک ماژول فوتر را نشکند
    (قاعده ۲)؛ ستونی که هیچ پیوندی ندارد، کلاً حذف می‌شود.
--}}

@php
    $legal = Route::has('workspace.legal.show');

    $columns = array_filter([
        'محصول' => array_filter([
            Route::has('encyclopedia.index') ? ['دانشنامه', route('encyclopedia.index')] : null,
            Route::has('tools.index') ? ['ابزارها', route('tools.index')] : null,
            Route::has('chemicals.index') ? ['بانک مواد شیمیایی', route('chemicals.index')] : null,
        ]),
        'فروشگاه' => array_filter([
            Route::has('commerce.index') ? ['فایل‌های تخصصی', route('commerce.index')] : null,
            Route::has('courses.index') ? ['دوره‌ها', route('courses.index')] : null,
            Route::has('commerce.sell') ? ['فروشنده شوید', route('commerce.sell')] : null,
        ]),
        'قوانین' => array_filter([
            $legal ? ['قوانین و مقررات', route('workspace.legal.show', 'terms')] : null,
            $legal ? ['حریم خصوصی', route('workspace.legal.show', 'privacy')] : null,
            $legal ? ['سلب مسئولیت', route('workspace.legal.show', 'disclaimer')] : null,
        ]),
    ]);

    $bottom = array_filter([
        $legal ? ['تماس با ما', route('workspace.legal.show', 'contact')] : null,
        Route::has('workspace.status') ? ['وضعیت سرویس', route('workspace.status')] : null,
    ]);

    $link = 'inline-flex min-h-touch items-center text-label text-muted no-underline hover:text-ink hover:no-underline';
@endphp

<footer data-print="hide" class="mt-auto shrink-0 border-t border-line bg-surface-2 px-6 pt-12 pb-6 md:px-gutter">
    <div class="grid grid-cols-2 gap-8 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">
        <div class="col-span-2 lg:col-span-1">
            <span class="text-h4 text-ink">{{ config('app.name') }}</span>
            <p class="mt-2 max-w-[20rem] text-label text-muted">میزکار فارسی متخصص بهداشت حرفه‌ای و ایمنی کار.</p>
        </div>

        @foreach ($columns as $heading => $links)
            <nav aria-label="{{ $heading }}">
                <h2 class="text-label font-semibold text-ink">{{ $heading }}</h2>
                <ul class="mt-2 list-none ps-0">
                    @foreach ($links as [$label, $url])
                        <li><a href="{{ $url }}" class="{{ $link }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>

    <div class="mt-8 flex flex-col gap-4 border-t border-line pt-4 md:flex-row md:items-start md:justify-between">
        {{-- متن حقوقی و اعتبار طراح به خواست صاحب سایت؛ پیوند اینستاگرام فقط یک
             لینک ساده است و هیچ اسکریپت یا تصویری از سرویس بیرونی بار نمی‌کند. --}}
        <div class="flex flex-col gap-1 text-note text-muted">
            <p>کلیه‌ی حقوق مادی و معنوی این سایت متعلق به فرابهداشت می‌باشد.</p>
            <p>
                طراحی شده با <span aria-label="عشق" role="img">❤️</span> توسط
                <a href="https://instagram.com/a.alireza.74" rel="noopener noreferrer" target="_blank"
                   data-numeric class="inline-flex min-h-touch items-center font-semibold text-body hover:text-primary">a.alireza.74</a>
            </p>
        </div>

        @if ($bottom !== [])
            <nav aria-label="پیوندهای سرویس" class="flex flex-wrap gap-x-5">
                @foreach ($bottom as [$label, $url])
                    <a href="{{ $url }}" class="{{ $link }}">{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</footer>
