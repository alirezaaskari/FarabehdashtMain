{{--
    تصویر متحرک قهرمان صفحه اصلی: کارشناس با صداسنج کنار دستگاه، عدد روی
    صداسنج بالا می‌رود، نتیجه روزانه ظاهر می‌شود و گزارش تیک می‌خورد — همان
    «از اندازه‌گیری تا گزارش» در هشت ثانیه.

    کارتونی و تخت، فقط با رنگ‌های معنایی (fill-* از توکن‌ها)؛ هیچ فایل یا
    سرویس بیرونی. حرکت فقط CSS است (`.scene-*` در app.css): با «کاهش حرکت»
    خاموش می‌شود و قاب آخر (همه‌چیز پیدا) می‌ماند، و دکمه توقف دارد (motion.js).
    عددها نمونه‌اند: ۸۸ دسی‌بل در ۶ ساعت یعنی LEX,8h برابر ۸۶٫۸.
--}}

<figure data-motion {{ $attributes->merge(['class' => 'scene relative']) }}>
    <svg viewBox="0 0 480 400" role="img" aria-labelledby="scene-title" class="h-auto w-full">
        <title id="scene-title">کارشناس با صداسنج تراز صدای دستگاه را اندازه می‌گیرد، نتیجه روزانه را می‌بیند و گزارش می‌سازد</title>

        {{-- پس‌زمینه و زمین --}}
        <circle cx="258" cy="206" r="176" class="fill-primary-soft" />
        <rect x="24" y="334" width="432" height="7" rx="3.5" class="fill-line-strong" />

        {{-- دستگاه --}}
        <g>
            <rect x="40" y="198" width="124" height="136" rx="16" class="fill-surface stroke-ink" stroke-width="3" />
            <rect x="58" y="216" width="88" height="34" rx="7" class="fill-primary-deep" />
            <rect x="66" y="226" width="30" height="5" rx="2.5" class="fill-primary-line" />
            <rect x="66" y="236" width="48" height="5" rx="2.5" class="fill-primary-line" />
            <g class="scene-spin">
                <circle cx="80" cy="292" r="20" class="fill-line-soft stroke-ink" stroke-width="3" />
                <path d="M80 276v32M64 292h32" class="stroke-ink" stroke-width="3" stroke-linecap="round" />
            </g>
            <circle cx="130" cy="292" r="8" class="scene-blink fill-caution" />
            <rect x="58" y="318" width="20" height="16" rx="3" class="fill-ink" />
            <rect x="126" y="318" width="20" height="16" rx="3" class="fill-ink" />
        </g>

        {{-- موج‌های صدا --}}
        <g class="fill-none stroke-primary" stroke-width="4" stroke-linecap="round">
            <path class="scene-wave" d="M176 238q14 22 0 44" />
            <path class="scene-wave [animation-delay:.45s]" d="M190 226q22 34 0 68" />
            <path class="scene-wave [animation-delay:.9s]" d="M204 214q30 46 0 92" />
        </g>

        {{-- کارشناس --}}
        <g>
            <rect x="268" y="276" width="18" height="56" rx="8" class="fill-ink" />
            <rect x="294" y="276" width="18" height="56" rx="8" class="fill-ink" />
            <ellipse cx="274" cy="334" rx="16" ry="7" class="fill-ink" />
            <ellipse cx="308" cy="334" rx="16" ry="7" class="fill-ink" />

            <rect x="254" y="192" width="72" height="96" rx="26" class="fill-primary" />
            <rect x="254" y="238" width="72" height="9" class="fill-caution-line" />
            <path d="M318 214l18 34" class="stroke-primary" stroke-width="16" stroke-linecap="round" />

            <g class="scene-bob">
                <circle cx="290" cy="160" r="29" class="fill-caution-soft stroke-ink" stroke-width="3" />
                <g class="scene-eyes">
                    <circle cx="280" cy="164" r="3.2" class="fill-ink" />
                    <circle cx="299" cy="164" r="3.2" class="fill-ink" />
                </g>
                <path d="M282 176q8 7 16 0" class="fill-none stroke-ink" stroke-width="3" stroke-linecap="round" />
                <path d="M259 154a31 31 0 0 1 62 0z" class="fill-caution stroke-ink" stroke-width="3" stroke-linejoin="round" />
                <rect x="252" y="150" width="76" height="9" rx="4.5" class="fill-caution stroke-ink" stroke-width="3" />
            </g>

            {{-- دست و صداسنج --}}
            <path d="M262 212l-32 24" class="stroke-primary" stroke-width="16" stroke-linecap="round" />
            <rect x="202" y="222" width="34" height="56" rx="8" class="fill-ink" />
            <rect x="207" y="229" width="24" height="15" rx="3" class="fill-primary-line" />
            <rect x="216" y="204" width="6" height="20" class="fill-ink" />
            <circle cx="219" cy="202" r="8" class="fill-line-strong stroke-ink" stroke-width="3" />
        </g>

        {{-- برچسب عدد لحظه‌ای صداسنج --}}
        <g>
            <path d="M206 170l12 18 6-18z" class="fill-surface stroke-ink" stroke-width="3" stroke-linejoin="round" />
            <rect x="160" y="126" width="112" height="46" rx="14" class="fill-surface stroke-ink" stroke-width="3" />
            <g class="fill-ink" font-weight="800" font-size="20" text-anchor="middle" direction="ltr">
                <text x="200" y="156" class="scene-reading-1">82.4</text>
                <text x="200" y="156" class="scene-reading-2">85.1</text>
                <text x="200" y="156" class="scene-reading-3">88.0</text>
            </g>
            <text x="248" y="155" class="fill-muted" font-size="12" font-weight="700" text-anchor="middle" direction="ltr">dB(A)</text>
        </g>

        {{-- نتیجه روزانه --}}
        <g class="scene-card">
            <rect x="302" y="38" width="156" height="78" rx="16" class="fill-surface stroke-ink" stroke-width="3" />
            <text x="380" y="66" class="fill-muted" font-size="13" font-weight="700" text-anchor="middle" direction="ltr">LEX,8h</text>
            <text x="380" y="98" class="fill-ink" font-size="24" font-weight="800" text-anchor="middle" direction="ltr">86.8 dB(A)</text>
        </g>

        {{-- گزارش و تیک بازبینی --}}
        <g class="scene-doc">
            <rect x="358" y="176" width="100" height="130" rx="12" class="fill-surface stroke-ink" stroke-width="3" />
            <rect x="372" y="194" width="52" height="7" rx="3.5" class="fill-ink" />
            <rect x="372" y="210" width="72" height="5" rx="2.5" class="fill-line-strong" />
            <rect x="372" y="222" width="60" height="5" rx="2.5" class="fill-line-strong" />
            <rect x="372" y="238" width="10" height="30" rx="2" class="fill-primary-line" />
            <rect x="388" y="228" width="10" height="40" rx="2" class="fill-primary" />
            <rect x="404" y="246" width="10" height="22" rx="2" class="fill-primary-line" />
            <rect x="372" y="280" width="30" height="14" rx="3" class="fill-none stroke-ink" stroke-width="2.5" />
        </g>
        <g class="scene-check">
            <circle cx="452" cy="182" r="21" class="fill-primary stroke-ink" stroke-width="3" />
            <path d="M442 182l7 7 13-14" class="fill-none stroke-surface" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
        </g>
    </svg>

    <button type="button" data-motion-toggle hidden
            class="absolute bottom-0 end-0 inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface px-4
                   text-note font-bold text-muted hover:text-ink">
        <span data-motion-label>توقف انیمیشن</span>
    </button>
</figure>
