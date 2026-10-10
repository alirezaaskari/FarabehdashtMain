@props(['pose', 'parts'])

{{--
    بدن کنار عنوان هر گام فرم پوسچر، با عضوی که گام درباره‌اش می‌پرسد پررنگ
    (هندسه در App\Modules\Tools\Domain\BodyFigure). تزئینی است: عنوان گام همان
    را به صفحه‌خوان می‌گوید.
--}}

@php
    $figure = \App\Modules\Tools\Domain\BodyFigure::of($pose, $parts);
@endphp

<svg viewBox="0 0 60 100" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'tool-body']) }}
     fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
    <g class="text-muted" stroke-width="1.5">
        @foreach ($figure->furniture as $path)
            <path d="{{ $path }}" />
        @endforeach
    </g>
    <g class="text-muted" stroke-width="3.5">
        @foreach ($figure->body as $path)
            <path d="{{ $path }}" />
        @endforeach
    </g>
    <g class="text-primary" stroke-width="4.5">
        @foreach ($figure->marked as $path)
            <path d="{{ $path }}" />
        @endforeach
    </g>
</svg>
