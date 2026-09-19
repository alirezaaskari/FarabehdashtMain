@props(['size' => 'md'])

{{--
    سلب مسئولیت — قاعده دائمی محصول.
    سه نسخه: کوتاه زیر ابزار · متوسط پای محتوا · بلند در گزارش و صفحات حقوقی.
    متن از بیرون می‌آید تا در آینده از پنل مدیریت ویرایش شود.
--}}

@php
    $text = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ][$size] ?? 'text-sm';
@endphp

<aside {{ $attributes->merge([
    'class' => 'flex gap-3 rounded-lg border border-caution-line bg-caution-soft p-4',
]) }}>
    <span class="mt-0.5 text-caution">
        <x-icon name="info" :size="19" :stroke="2" />
    </span>
    <p class="{{ $text }} text-caution-ink">{{ $slot }}</p>
</aside>
