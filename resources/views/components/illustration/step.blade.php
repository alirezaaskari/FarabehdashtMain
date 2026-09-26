@props(['kind'])

{{--
    تصویر کوچک هر قدم «سه قدم تا گزارش»: اندازه‌گیری، نتیجه، گزارش.
    تزئینی و ایستا؛ عنوان و متن قدم کنارش همه‌چیز را می‌گوید.
--}}

<svg viewBox="0 0 200 130" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'h-auto w-full']) }}>
    <rect x="0" y="0" width="200" height="130" rx="18" class="fill-primary-soft" />

    @switch($kind)
        @case('measure')
            <rect x="84" y="36" width="36" height="62" rx="8" class="fill-ink" />
            <rect x="90" y="44" width="24" height="16" rx="3" class="fill-primary-line" />
            <rect x="99" y="20" width="6" height="18" class="fill-ink" />
            <circle cx="102" cy="18" r="9" class="fill-line-strong stroke-ink" stroke-width="3" />
            <g class="fill-none stroke-primary" stroke-width="4" stroke-linecap="round">
                <path d="M136 44q12 18 0 36" />
                <path d="M148 34q20 28 0 56" />
                <path d="M68 44q-12 18 0 36" />
                <path d="M56 34q-20 28 0 56" />
            </g>
            @break

        @case('result')
            <rect x="40" y="24" width="120" height="82" rx="14" class="fill-surface stroke-ink" stroke-width="3" />
            <path d="M64 86a36 36 0 0 1 72 0" class="fill-none stroke-line" stroke-width="10" stroke-linecap="round" />
            <path d="M64 86a36 36 0 0 1 58 -28" class="fill-none stroke-caution" stroke-width="10" stroke-linecap="round" />
            <path d="M100 86l18 -22" class="stroke-ink" stroke-width="4" stroke-linecap="round" />
            <circle cx="100" cy="86" r="6" class="fill-ink" />
            @break

        @default
            <rect x="66" y="16" width="70" height="98" rx="10" class="fill-surface stroke-ink" stroke-width="3" />
            <rect x="78" y="30" width="38" height="6" rx="3" class="fill-ink" />
            <rect x="78" y="44" width="46" height="5" rx="2.5" class="fill-line-strong" />
            <rect x="78" y="55" width="40" height="5" rx="2.5" class="fill-line-strong" />
            <rect x="78" y="66" width="44" height="5" rx="2.5" class="fill-line-strong" />
            <rect x="78" y="86" width="20" height="16" rx="3" class="fill-none stroke-ink" stroke-width="2.5" />
            <circle cx="138" cy="22" r="16" class="fill-primary stroke-ink" stroke-width="3" />
            <path d="M130 22l6 6 10-11" class="fill-none stroke-surface" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
    @endswitch
</svg>
