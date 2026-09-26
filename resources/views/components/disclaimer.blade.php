@props(['size' => 'md'])

{{--
    سلب مسئولیت — قاعده دائمی محصول.
    سه نسخه: کوتاه زیر ابزار · متوسط پای محتوا · بلند در گزارش و صفحات حقوقی.
    متن از بیرون می‌آید تا در آینده از پنل مدیریت ویرایش شود.
--}}

@php
    // sm و md هر دو text-note هستند و این عمدی است: نسخه کوتاه پیش‌تر ۱۲ پیکسل
    // بود، که زیر «کمترین اندازه مجاز» سیستم طراحی است. تفاوتشان در بالشتک و
    // طول متن می‌ماند، نه در اندازه قلم.
    $text = [
        'sm' => 'text-note',
        'md' => 'text-note',
        'lg' => 'text-copy',
    ][$size] ?? 'text-note';
@endphp

<aside {{ $attributes->merge([
    'class' => 'flex items-start gap-3 rounded-lg border border-line bg-surface-2 px-5 py-4',
]) }}>
    <span class="mt-1 shrink-0 text-caution">
        <x-icon name="info" :size="18" :stroke="2" />
    </span>
    <p class="{{ $text }} leading-8 text-body">{{ $slot }}</p>
</aside>
