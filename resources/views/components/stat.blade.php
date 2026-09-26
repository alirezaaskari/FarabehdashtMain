@props(['label', 'value', 'unit' => null, 'note' => null, 'tone' => 'surface', 'size' => 'md', 'numeric' => true])

{{--
    کارت آمار — برچسب ۱۳، عدد ۲۸ (یا ۶۴ در حالت متریک).
    tone='primary' کارت نتیجه اصلی ابزار است: زمینه سبز پررنگ و عدد سفید.

    numeric=true یعنی مقدار اندازه‌گیری است: لاتین، چپ‌به‌راست و جدا از واحدش.
    numeric=false برای شمارش در جمله فارسی است؛ آن‌وقت ارقام فارسی را خود
    فراخوان با PersianNumber می‌سازد.
--}}

@php
    $isPrimary = $tone === 'primary';
    $isMetric = $size === 'metric';
    $valueSize = $isMetric ? 'text-metric' : 'text-stat';
    $padding = $isMetric ? 'px-6 py-8 md:px-9' : 'px-6 py-5.5';
@endphp

<div {{ $attributes->merge([
    'class' => 'rounded-xl border '.$padding.' '
        .($isPrimary ? 'bg-primary border-transparent' : 'bg-surface border-line'),
]) }}>
    <span class="block {{ $isMetric ? 'text-copy font-semibold' : 'text-note font-semibold' }} {{ $isPrimary ? 'text-primary-line' : 'text-muted' }}">
        {{ $label }}
    </span>

    {{--
        عدد و واحد با هم یک بلوک چپ‌به‌راست‌اند تا «°C» وارونه نشود و واحد
        سمت راست عدد بماند، همان‌طور که در متن علمی خوانده می‌شود.
    --}}
    <div class="{{ $isMetric ? 'mt-3.5' : 'mt-2' }} flex items-baseline gap-2" @if ($numeric) data-numeric @endif>
        <span class="{{ $valueSize }} {{ $isPrimary ? 'text-on-primary' : 'text-ink' }}">
            {{ $value }}
        </span>
        @if ($unit)
            <span class="{{ $isMetric ? 'text-stat' : 'text-label' }} font-semibold {{ $isPrimary ? 'text-primary-line' : 'text-muted' }}">{{ $unit }}</span>
        @endif
    </div>

    @if ($note)
        <span class="mt-1.5 block text-note {{ $isPrimary ? 'text-primary-line' : 'text-muted' }}">{{ $note }}</span>
    @endif
</div>
