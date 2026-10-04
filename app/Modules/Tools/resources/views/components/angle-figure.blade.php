@props(['segment', 'ranges'])

{{--
    شکل خطی بازه زاویه یک گزینه پوسچر (هندسه در App\Modules\Tools\Domain\AngleFigure).
    تزئینی است: معنای گزینه در برچسب متنی کنار آن آمده و صفحه‌خوان آن را می‌خواند.
--}}

@php
    $figure = \App\Modules\Tools\Domain\AngleFigure::of($segment, $ranges);
@endphp

<svg viewBox="0 0 100 100" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'tool-figure']) }}
     fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
    <g class="text-muted" stroke-width="5">
        @foreach ($figure->context as $path)
            <path d="{{ $path }}" />
        @endforeach
    </g>
    <line class="text-muted" x1="{{ $figure->reference[0] }}" y1="{{ $figure->reference[1] }}"
          x2="{{ $figure->reference[2] }}" y2="{{ $figure->reference[3] }}" stroke-width="1.5" stroke-dasharray="3 4" />
    <g class="text-primary">
        @foreach ($figure->wedges as $path)
            <path d="{{ $path }}" fill="currentColor" fill-opacity="0.2" stroke-width="1.5" />
        @endforeach
        @foreach ($figure->limbs as [$x1, $y1, $x2, $y2])
            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke-width="5" />
        @endforeach
    </g>
    @if ($figure->head)
        <circle class="text-muted" cx="{{ $figure->head[0] }}" cy="{{ $figure->head[1] }}" r="7" stroke-width="3.5" />
    @endif
    @foreach ($figure->tips as [$cx, $cy])
        <circle class="text-primary" cx="{{ $cx }}" cy="{{ $cy }}" r="7" stroke-width="3.5" />
    @endforeach
    <circle cx="{{ $figure->reference[0] }}" cy="{{ $figure->reference[1] }}" r="4" class="text-ink" fill="currentColor" stroke="none" />
</svg>
