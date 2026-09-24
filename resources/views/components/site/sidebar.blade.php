@props(['active' => null, 'title' => 'میزکار'])

{{--
    ستون کناری میزکار — کارت ۲۴۰ پیکسلی پروتوتایپ.

    هر ردیف با Route::has محافظت شده است: با حذف یک ماژول، سطر مربوط به آن
    خودش می‌رود و چیدمان نمی‌شکند (قاعده ۲). فقط مقصدهای واقعی فهرست می‌شوند؛
    ردیف مرده در ستون کناری، کاربر را سردرگم می‌کند.
--}}

@php
    $items = array_values(array_filter([
        Route::has('tools.calculations.index')
            ? ['calculations', 'محاسبات ذخیره‌شده', route('tools.calculations.index')] : null,
        Route::has('projects.index')
            ? ['projects', 'پروژه‌های اندازه‌گیری', route('projects.index')] : null,
        Route::has('projects.equipment.index')
            ? ['equipment', 'دفترچه تجهیزات', route('projects.equipment.index')] : null,
        Route::has('projects.calendar')
            ? ['calendar', 'تقویم الزامات پایش', route('projects.calendar')] : null,
        Route::has('identity.profiles')
            ? ['profiles', 'نقش‌ها و پروفایل‌ها', route('identity.profiles')] : null,
        // ردیف اشتراک با خاموش‌شدن کلید درآمدزایی هم می‌رود، نه فقط با حذف
        // ماژول: متغیر را ماژول درآمدزایی با یک View Composer می‌گذارد و
        // نبودنش یعنی «اشتراکی در کار نیست».
        (($proSubscriptionOffered ?? false) && Route::has('monetization.plans'))
            ? ['pro', 'اشتراک حرفه‌ای', route('monetization.plans')] : null,
    ]));
@endphp

@if ($items !== [])
    <aside data-print="hide"
           class="w-full shrink-0 rounded-xl border border-line bg-surface p-5 md:w-60">
        <h2 class="mb-3.5 text-xs font-bold text-muted">{{ $title }}</h2>

        <nav aria-label="{{ $title }}" class="flex flex-col gap-1">
            @foreach ($items as [$key, $label, $url])
                <a href="{{ $url }}"
                   @class([
                       'flex min-h-touch items-center rounded-md px-3 py-2 text-sm'
                           .' no-underline hover:no-underline',
                       'bg-primary-soft font-bold text-on-primary-soft' => $active === $key,
                       'font-semibold text-muted hover:bg-surface-2 hover:text-ink' => $active !== $key,
                   ])
                   @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
@endif
