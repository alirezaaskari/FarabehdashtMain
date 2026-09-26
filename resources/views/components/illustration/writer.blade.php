{{--
    تصویر بخش «نویسنده شو»: نویسنده پشت لپ‌تاپ، خط‌های مقاله روی صفحه نوشته
    می‌شوند و کارت مقاله تیک بازبینی می‌خورد.

    تزئینی است (متن کنارش همه‌چیز را می‌گوید)، پس از صفحه‌خوان پنهان است.
    همان قواعد `measure-scene`: فقط رنگ معنایی، حرکت CSS، خاموش با «کاهش حرکت».
--}}

<svg viewBox="0 0 360 300" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'scene h-auto w-full']) }}>
    <circle cx="186" cy="150" r="136" class="fill-surface" />
    <rect x="30" y="262" width="300" height="6" rx="3" class="fill-primary-line" />

    {{-- میز --}}
    <rect x="40" y="206" width="280" height="12" rx="6" class="fill-ink" />
    <rect x="62" y="218" width="12" height="46" rx="4" class="fill-ink" />
    <rect x="286" y="218" width="12" height="46" rx="4" class="fill-ink" />

    {{-- نویسنده --}}
    <g>
        <rect x="66" y="146" width="66" height="62" rx="24" class="fill-primary" />
        <path d="M122 172l38 24" class="stroke-primary" stroke-width="15" stroke-linecap="round" />
        <g class="scene-bob">
            <circle cx="99" cy="118" r="25" class="fill-caution-soft stroke-ink" stroke-width="3" />
            <path d="M74 116a25 25 0 0 1 50-4c-12 1-22-5-26-12-5 8-13 14-24 16z" class="fill-ink" />
            <g class="scene-eyes">
                <circle cx="104" cy="122" r="3" class="fill-ink" />
                <circle cx="116" cy="121" r="3" class="fill-ink" />
            </g>
            <path d="M104 132q6 5 12 0" class="fill-none stroke-ink" stroke-width="3" stroke-linecap="round" />
        </g>
    </g>

    {{-- لپ‌تاپ و خط‌هایی که نوشته می‌شوند --}}
    <g>
        <rect x="160" y="124" width="124" height="82" rx="9" class="fill-ink" />
        <rect x="168" y="132" width="108" height="66" rx="5" class="fill-surface" />
        <rect x="178" y="142" width="54" height="7" rx="3.5" class="scene-type fill-primary" />
        <rect x="178" y="157" width="86" height="5" rx="2.5" class="scene-type fill-line-strong [animation-delay:.6s]" />
        <rect x="178" y="169" width="74" height="5" rx="2.5" class="scene-type fill-line-strong [animation-delay:1.2s]" />
        <rect x="178" y="181" width="60" height="5" rx="2.5" class="scene-type fill-line-strong [animation-delay:1.8s]" />
        <path d="M150 206h144l-10 -6h-124z" class="fill-line-strong stroke-ink" stroke-width="2" stroke-linejoin="round" />
    </g>

    {{-- کارت مقاله منتشرشده --}}
    <g class="scene-float">
        <rect x="226" y="30" width="108" height="76" rx="12" class="fill-surface stroke-ink" stroke-width="3" />
        <rect x="240" y="46" width="56" height="7" rx="3.5" class="fill-ink" />
        <rect x="240" y="62" width="78" height="5" rx="2.5" class="fill-line-strong" />
        <rect x="240" y="74" width="64" height="5" rx="2.5" class="fill-line-strong" />
        <rect x="240" y="86" width="40" height="5" rx="2.5" class="fill-line-strong" />
    </g>
    <g class="scene-check">
        <circle cx="332" cy="36" r="17" class="fill-primary stroke-ink" stroke-width="3" />
        <path d="M324 36l6 6 10-11" class="fill-none stroke-surface" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
    </g>

    {{-- مداد --}}
    <g class="scene-wiggle">
        <path d="M300 120l14-6 20 64-14 6z" class="fill-caution stroke-ink" stroke-width="3" stroke-linejoin="round" />
        <path d="M320 184l14-6 2 18z" class="fill-caution-soft stroke-ink" stroke-width="3" stroke-linejoin="round" />
    </g>
</svg>
