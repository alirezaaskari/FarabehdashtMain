@props(['active' => null, 'title' => 'میزکار'])

{{--
    ستون کناری میزکار — کارت ۲۴۰ پیکسلی پروتوتایپ.

    هر ردیف با Route::has محافظت شده است: با حذف یک ماژول، سطر مربوط به آن
    خودش می‌رود و چیدمان نمی‌شکند (قاعده ۲). فقط مقصدهای واقعی فهرست می‌شوند؛
    ردیف مرده در ستون کناری، کاربر را سردرگم می‌کند.
--}}

@php
    $unread = (int) ($unreadNotifications ?? 0);

    $items = array_values(array_filter([
        Route::has('workspace.dashboard')
            ? ['dashboard', 'میزکار', route('workspace.dashboard')] : null,
        // شمار خوانده‌نشده را ماژول میزکار با View Composer می‌گذارد.
        Route::has('workspace.notifications')
            ? ['notifications', $unread > 0 ? 'اعلان‌ها ('.\App\Support\PersianDigits::from($unread).')' : 'اعلان‌ها', route('workspace.notifications')] : null,
        Route::has('workspace.wallet')
            ? ['wallet', 'کیف پول', route('workspace.wallet')] : null,
        Route::has('tools.calculations.index')
            ? ['calculations', 'محاسبات ذخیره‌شده', route('tools.calculations.index')] : null,
        Route::has('projects.index')
            ? ['projects', 'پروژه‌های اندازه‌گیری', route('projects.index')] : null,
        Route::has('projects.equipment.index')
            ? ['equipment', 'دفترچه تجهیزات', route('projects.equipment.index')] : null,
        Route::has('reports.index')
            ? ['reports', 'گزارش‌ها', route('reports.index')] : null,
        Route::has('projects.calendar')
            ? ['calendar', 'تقویم الزامات پایش', route('projects.calendar')] : null,
        // پنل فروشنده و مدرس فقط برای کسی که آن نقش را دارد.
        (Route::has('commerce.vendor.products.index') && auth()->user()?->can('products.manage'))
            ? ['vendor-products', 'محصولات من', route('commerce.vendor.products.index')] : null,
        (Route::has('courses.instructor.courses.index') && auth()->user()?->can('courses.manage'))
            ? ['instructor-courses', 'دوره‌های من', route('courses.instructor.courses.index')] : null,
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
    @php $current = collect($items)->firstWhere(0, $active)[1] ?? null; @endphp

    {{-- روی موبایل جمع است و بخش فعلی را نشان می‌دهد؛ وگرنه ۹ ردیف پیمایش
         پیش از عنوان صفحه می‌نشست. --}}
    <aside data-print="hide" class="w-full shrink-0 md:w-60">
        <x-disclosure open-from="md" class="rounded-xl border border-line bg-surface px-5 py-2 md:py-5">
            <x-slot:summary>
                <h2 class="text-label font-bold text-muted md:text-note">
                    {{ $title }}@if ($current && $current !== $title)<span class="md:hidden"> · <span class="text-ink">{{ $current }}</span></span>@endif
                </h2>
            </x-slot:summary>

            <nav aria-label="{{ $title }}" class="flex flex-col gap-1 pb-3 md:pb-0">
                @foreach ($items as [$key, $label, $url])
                    <a href="{{ $url }}"
                       @class([
                           'flex min-h-touch items-center rounded-md px-3 py-2 text-label'
                               .' no-underline hover:no-underline',
                           'bg-primary-soft font-bold text-on-primary-soft' => $active === $key,
                           'font-semibold text-muted hover:bg-surface-2 hover:text-ink' => $active !== $key,
                       ])
                       @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </x-disclosure>
    </aside>
@endif
