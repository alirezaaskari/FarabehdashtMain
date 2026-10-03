@props(['active' => null, 'context' => 'site'])

{{--
    نوار پایین موبایل — زیر ۷۶۸ پیکسل، همان پنج مقصد آرت‌بوردهای موبایل
    پروتوتایپ. شست روی گوشی به بالای صفحه نمی‌رسد؛ مقصدهای پرتکرار باید
    پایین باشند. فهرست کامل همچنان در منوی کشویی سربرگ است.

    context="workspace": کاربرِ واردشده در میزکار کار روزانه‌اش را می‌خواهد،
    نه فروشگاه؛ نوار به میزکار، محاسبات، پروژه‌ها، گزارش‌ها و اعلان‌ها
    عوض می‌شود و active کلید ستون کناری است. مهمانی که ابزار را در همان
    پوسته باز کرده نوار عمومی را می‌بیند.

    هر مقصد با Route::has محافظت می‌شود (قاعده ۲). active همان کلید پیمایش
    سربرگ است، به‌اضافه home و workspace.
--}}

@php
    if ($context === 'workspace' && auth()->check()) {
        $items = array_values(array_filter([
            Route::has('workspace.dashboard') ? ['dashboard', 'میزکار', route('workspace.dashboard'), 'home'] : null,
            Route::has('tools.calculations.index') ? ['calculations', 'محاسبات', route('tools.calculations.index'), 'calculator'] : null,
            Route::has('projects.index') ? ['projects', 'پروژه‌ها', route('projects.index'), 'pulse'] : null,
            Route::has('reports.index') ? ['reports', 'گزارش‌ها', route('reports.index'), 'file'] : null,
            Route::has('workspace.notifications') ? ['notifications', 'اعلان‌ها', route('workspace.notifications'), 'bell'] : null,
        ]));
    } else {
        $account = auth()->check()
            ? (Route::has('workspace.dashboard') ? ['workspace', 'میزکار', route('workspace.dashboard')] : null)
            : (Route::has('login') ? ['login', 'ورود', route('login')] : null);

        $items = array_values(array_filter([
            ['home', 'خانه', Route::has('home') ? route('home') : '/', 'home'],
            Route::has('tools.index') ? ['tools', 'ابزارها', route('tools.index'), 'pulse'] : null,
            Route::has('chemicals.index') ? ['chemicals', 'مواد', route('chemicals.index'), 'chemical'] : null,
            Route::has('commerce.index') ? ['market', 'فروشگاه', route('commerce.index'), 'bag'] : null,
            $account ? [...$account, 'user'] : null,
        ]));

        // پوسته میزکار برای مهمان: ردیف «ورود» یا «میزکار» همان active قدیمی است.
        $active = $context === 'workspace' ? 'workspace' : $active;
    }

    $active ??= request()->routeIs('home') ? 'home' : null;
@endphp

{{-- فاصله‌گذار هم‌رنگ فوتر، تا نوار ثابت پایین فوتر را نپوشاند. --}}
<div aria-hidden="true" data-print="hide" class="h-16 shrink-0 bg-ink md:hidden"></div>

<nav aria-label="پیمایش سریع" data-print="hide"
     class="fixed inset-x-0 bottom-0 z-30 flex h-16 border-t border-line bg-surface md:hidden">
    @foreach ($items as [$key, $label, $url, $icon])
        <a href="{{ $url }}"
           @class([
               'flex min-w-0 flex-1 flex-col items-center justify-center gap-1 text-note no-underline hover:no-underline',
               'font-semibold text-primary' => $active === $key,
               'font-semibold text-muted hover:text-ink' => $active !== $key,
           ])
           @if ($active === $key) aria-current="page" @endif>
            <x-icon :name="$icon" :size="20" />
            {{ $label }}
        </a>
    @endforeach
</nav>
