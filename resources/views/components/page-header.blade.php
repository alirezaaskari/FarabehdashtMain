@props(['title', 'lede' => null, 'size' => 'h1', 'art' => null])

{{--
    بلوک تیتر صفحه — همان چیدمان پروتوتایپ: تیتر و بند معرفی در یک سو،
    دکمه‌های اقدام در سوی دیگر، هم‌تراز از پایین.

    size = 'display' برای صفحه اصلی و صفحه ابزار، 'h1' برای بقیه.
    اسلات‌ها: actions (دکمه‌ها) و meta (ستون فراداده مثل نسخه و منبع).
    art = نام تصویر خطی (x-art): روی موبایل بالای تیتر، از lg در سوی دیگر آن؛ در
    عرض تبلت پنهان است تا کنار دکمه‌ها و ستون کناری میزکار سرریز نکند.
--}}

<header {{ $attributes->merge(['class' => 'flex flex-col gap-5 md:flex-row md:items-end md:justify-between md:gap-10']) }}>
    <div class="min-w-0">
        <h1 class="{{ $size === 'display' ? 'text-display' : 'text-h1' }} text-ink">{{ $title }}</h1>

        @if ($lede)
            <p class="mt-3 max-w-[42rem] text-lede text-muted">{{ $lede }}</p>
        @endif

        {{ $slot }}
    </div>

    @isset($meta)
        {{-- سقف عرض تا عنوان لاتین بلند یک منبع (ISO …) ستون را از صفحه بیرون نزند. --}}
        <div class="flex min-w-0 shrink-0 flex-col gap-2 md:max-w-80 md:items-end">{{ $meta }}</div>
    @endisset

    @isset($actions)
        <div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>
    @endisset

    @if ($art)
        <x-art :name="$art" class="order-first h-24 w-auto shrink-0 self-start md:hidden lg:order-none lg:block lg:h-36 lg:self-end" />
    @endif
</header>
