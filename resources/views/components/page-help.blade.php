@props(['topic'])

{{--
    راهنمای بخش: «این بخش به چه کار می‌آید؟» — هدف، کاربردها و یک مثال.
    متن از config/help.php می‌آید تا همه راهنماها یک‌جا نوشته و بازبینی شوند.
    جمع است تا کاربر آشنا را معطل نکند؛ details بدون جاوااسکریپت هم کار می‌کند.
--}}

@php($help = config('help.'.$topic))

@if (is_array($help))
    <details data-page-help="{{ $topic }}" data-print="hide"
             {{ $attributes->class('group rounded-lg border border-line bg-surface-2') }}>
        <summary class="flex min-h-touch cursor-pointer list-none items-center gap-3 px-4 text-label font-bold text-ink
                        [&::-webkit-details-marker]:hidden">
            <span class="text-primary"><x-icon name="bulb" :size="19" /></span>
            <span class="grow">این بخش به چه کار می‌آید؟</span>
            <span class="text-muted transition-transform group-open:rotate-180">
                <x-icon name="chevron-down" :size="20" />
            </span>
        </summary>

        <div class="space-y-4 px-4 pb-4 text-copy text-body">
            <p>{{ \App\Support\Help\HelpText::render($help['purpose']) }}</p>

            @if (! empty($help['uses']))
                <div>
                    <p class="text-label font-bold text-ink">کاربردها</p>
                    <ul class="mt-2 list-disc space-y-1.5 ps-5">
                        @foreach ($help['uses'] as $use)
                            <li>{{ \App\Support\Help\HelpText::render($use) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($help['example']))
                <div class="rounded-md border-s-4 border-primary-line bg-surface p-3">
                    <p class="text-label font-bold text-ink">مثال</p>
                    <p class="mt-1">{{ \App\Support\Help\HelpText::render($help['example']) }}</p>
                </div>
            @endif
        </div>
    </details>
@endif
