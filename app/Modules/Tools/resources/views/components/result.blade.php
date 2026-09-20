@props(['rows', 'notes' => []])

{{--
    نتیجه محاسبه — کارت سبز پررهنگ پروتوتایپ.
    خروجی اول عدد اصلی است و با اندازه متریک نمایش داده می‌شود؛ بقیه خروجی‌ها
    کارت‌های معمولی زیر آن‌اند.

    رابطه، منبع و نسخه اینجا تکرار نمی‌شوند: جعبه «فرمول به‌کاررفته» کنار
    ورودی‌ها و بخش «درباره این ابزار» آن‌ها را نگه می‌دارند.
--}}

@php
    $primary = $rows[0] ?? null;
    $rest = array_slice($rows, 1);
@endphp

<div class="flex flex-col gap-4">

    @if ($primary)
        <x-stat :label="'نتیجه محاسبه — '.$primary->label"
                :value="$primary->value"
                :unit="$primary->unit"
                tone="primary"
                size="metric" />
    @endif

    @if ($rest !== [])
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($rest as $row)
                <x-stat :label="$row->label" :value="$row->value" :unit="$row->unit" />
            @endforeach
        </div>
    @endif

    @foreach ($notes as $note)
        <x-alert tone="caution">{{ $note }}</x-alert>
    @endforeach

</div>
