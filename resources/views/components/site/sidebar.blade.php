@props(['active' => null, 'title' => 'میزکار'])

{{--
    ستون کناری میزکار — کارت ۲۴۰ پیکسلی پروتوتایپ، در سه گروه با آیکون.

    هر ردیف با Route::has محافظت شده است: با حذف یک ماژول، سطر مربوط به آن
    خودش می‌رود و چیدمان نمی‌شکند (قاعده ۲). گروهی که ردیفی برایش نماند
    عنوانش هم نمی‌آید. فقط مقصدهای واقعی فهرست می‌شوند؛ ردیف مرده در ستون
    کناری، کاربر را سردرگم می‌کند.
--}}

@php
    $unread = (int) ($unreadNotifications ?? 0);
    $user = auth()->user();

    // هر ردیف: [کلید، برچسب، نشانی، آیکون].
    $groups = array_filter([
        'کار من' => array_values(array_filter([
            Route::has('workspace.dashboard')
                ? ['dashboard', 'میزکار', route('workspace.dashboard'), 'home'] : null,
            Route::has('tools.calculations.index')
                ? ['calculations', 'محاسبات ذخیره‌شده', route('tools.calculations.index'), 'calculator'] : null,
            Route::has('projects.index')
                ? ['projects', 'پروژه‌های اندازه‌گیری', route('projects.index'), 'pulse'] : null,
            Route::has('projects.equipment.index')
                ? ['equipment', 'دفترچه تجهیزات', route('projects.equipment.index'), 'list'] : null,
            Route::has('reports.index')
                ? ['reports', 'گزارش‌ها', route('reports.index'), 'file'] : null,
            Route::has('projects.calendar')
                ? ['calendar', 'تقویم الزامات پایش', route('projects.calendar'), 'calendar'] : null,
            (Route::has('encyclopedia.writing.index') && $user?->can('content.write'))
                ? ['writing', 'نوشته‌های دانشنامه', route('encyclopedia.writing.index'), 'bulb'] : null,
            Route::has('expert.mine')
                ? ['my-questions', 'پرسش‌های من', route('expert.mine'), 'bulb'] : null,
            (Route::has('expert.queue') && $user?->can('expert.answer'))
                ? ['expert-queue', 'پرسش‌های باز برای پاسخ', route('expert.queue'), 'list'] : null,
        ])),
        'یادگیری و خرید' => array_values(array_filter([
            Route::has('courses.mine')
                ? ['my-courses', 'دوره‌های من', route('courses.mine'), 'book'] : null,
            Route::has('commerce.purchases')
                ? ['purchases', 'خریدهای من', route('commerce.purchases'), 'bag'] : null,
            // پنل فروشنده و مدرس فقط برای کسی که آن نقش را دارد.
            (Route::has('commerce.vendor.products.index') && $user?->can('products.manage'))
                ? ['vendor-products', 'محصولات فروشگاه من', route('commerce.vendor.products.index'), 'upload'] : null,
            (Route::has('courses.instructor.courses.index') && $user?->can('courses.manage'))
                ? ['instructor-courses', 'دوره‌های تدریس من', route('courses.instructor.courses.index'), 'compass'] : null,
        ])),
        'حساب' => array_values(array_filter([
            Route::has('identity.account')
                ? ['account', 'حساب من', route('identity.account'), 'user'] : null,
            // شمار خوانده‌نشده را ماژول میزکار با View Composer می‌گذارد.
            Route::has('workspace.notifications')
                ? ['notifications', $unread > 0 ? 'اعلان‌ها ('.\App\Support\PersianDigits::from($unread).')' : 'اعلان‌ها', route('workspace.notifications'), 'bell'] : null,
            Route::has('workspace.wallet')
                ? ['wallet', 'کیف پول', route('workspace.wallet'), 'wallet'] : null,
            // ردیف اشتراک با خاموش‌شدن کلید درآمدزایی هم می‌رود، نه فقط با حذف
            // ماژول: متغیر را ماژول درآمدزایی با یک View Composer می‌گذارد و
            // نبودنش یعنی «اشتراکی در کار نیست».
            (($proSubscriptionOffered ?? false) && Route::has('monetization.plans'))
                ? ['pro', 'اشتراک حرفه‌ای', route('monetization.plans'), 'badge'] : null,
            Route::has('identity.profiles')
                ? ['profiles', 'نقش‌ها و پروفایل‌ها', route('identity.profiles'), 'shield'] : null,
        ])),
    ]);
@endphp

@if ($groups !== [])
    @php $current = collect($groups)->flatten(1)->firstWhere(0, $active)[1] ?? null; @endphp

    {{-- روی موبایل جمع است و بخش فعلی را نشان می‌دهد؛ وگرنه ده‌ها ردیف پیمایش
         پیش از عنوان صفحه می‌نشست. --}}
    <aside data-print="hide" class="w-full shrink-0 md:w-60">
        <x-disclosure open-from="md" class="rounded-lg border border-line bg-surface px-4 py-2 md:border-0 md:bg-transparent md:p-0">
            <x-slot:summary>
                <h2 class="text-label font-semibold text-muted md:text-note">
                    {{ $title }}@if ($current && $current !== $title)<span class="md:hidden"> · <span class="text-ink">{{ $current }}</span></span>@endif
                </h2>
            </x-slot:summary>

            <nav aria-label="{{ $title }}" class="flex flex-col gap-4 pb-3 md:pb-0">
                @foreach ($groups as $heading => $items)
                    <div role="group" class="flex flex-col gap-1" aria-label="{{ $heading }}">
                        <h3 class="px-3 text-note font-semibold text-muted" aria-hidden="true">{{ $heading }}</h3>
                        @foreach ($items as [$key, $label, $url, $icon])
                            <a href="{{ $url }}"
                               @class([
                                   'flex min-h-touch items-center gap-2.5 rounded-md px-3 py-2 text-label'
                                       .' no-underline hover:no-underline',
                                   'bg-surface-2 font-semibold text-ink [&_svg]:text-primary' => $active === $key,
                                   'font-medium text-body hover:bg-surface-2 hover:text-ink [&_svg]:text-muted' => $active !== $key,
                               ])
                               @if ($active === $key) aria-current="page" @endif>
                                <x-icon :name="$icon" :size="18" class="shrink-0" />
                                <span>{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </x-disclosure>
    </aside>
@endif
