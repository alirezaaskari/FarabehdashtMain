@props(['script'])

{{--
    آموزش متحرک یک بخش: صحنه، کنترل‌ها و زیرنویس. زمان‌بندی را
    App\Support\Help\Motion\MotionScript ساخته و resources/js/motion.js پخش می‌کند.

    خودکار پخش نمی‌شود و فایل ویدیو یا سرویس بیرونی ندارد. بدون جاوااسکریپت
    متن صحنه‌ها به‌صورت فهرست دیده می‌شود؛ صحنه تزئینی است (aria-hidden) و
    زیرنویس aria-live همان متن را می‌خواند.
--}}

@php
    /** @var \App\Support\Help\Motion\MotionScript $script */
    $range = \App\Support\Help\Motion\MotionScript::range(...);
@endphp

<figure data-motion="{{ $script->key }}" data-duration="{{ $script->duration }}" data-poster="{{ $script->poster }}"
        data-print="hide" {{ $attributes->class('m-0') }}>
    <div data-motion-viewport class="motion-viewport" role="img"
         aria-label="{{ $script->title }}؛ متن هر صحنه زیر نمایش آمده است">
        <div data-motion-stage class="motion-stage" aria-hidden="true">

            @foreach ($script->scenes as $scene)
                @if ($scene['kind'] === 'card')
                    @php
                        $hasArt = ! empty($scene['art']) && view()->exists('components.art.pictures.'.$scene['art']);
                    @endphp
                    <div data-show="{{ $scene['show'] }}" @class(['mo-card', 'is-wide' => ! $hasArt])>
                        @if (! empty($scene['kicker']))
                            <span class="mo-kicker">{{ $scene['kicker'] }}</span>
                        @endif
                        <span class="mo-heading">{{ $scene['heading'] }}</span>
                        @if (! empty($scene['text']))
                            <span class="mo-lede">{{ \App\Support\Help\HelpText::render($scene['text']) }}</span>
                        @endif
                        @if ($scene['chips'] !== [])
                            <span class="mo-chips">
                                @foreach ($scene['chips'] as $i => $chip)
                                    <span class="mo-chip" data-show="{{ $range($scene['start'] + 0.8 + $i * 0.35) }}">@fa($i + 1) {{ $chip }}</span>
                                @endforeach
                            </span>
                        @endif
                    </div>
                    @if ($hasArt)
                        <div class="mo-art" data-show="{{ $scene['show'] }}" data-enter="0 0 0.96">
                            <x-art :name="$scene['art']" />
                        </div>
                    @endif
                @endif
            @endforeach

            @if ($script->window !== null)
                @php
                    $dim = collect($script->scenes)->flatMap(fn ($s) => $s['widgets'] ?? [])->firstWhere('type', 'sheet');
                @endphp
                <div data-show="{{ $script->window }}" data-enter="0 12 1"
                     @if ($dim) data-dim="{{ $range($dim['at'], $dim['at'] + 0.8) }}" @endif
                     @class(['mo-window', 'has-steps' => $script->steps !== []])>
                    <div class="mo-bar">
                        @foreach ($script->urls as $url)
                            <span class="mo-url" data-numeric data-show="{{ $url['show'] }}" data-enter="0 0 1">{{ $url['url'] }}</span>
                        @endforeach
                    </div>

                    @if ($script->steps !== [])
                        <div class="mo-steps">
                            @foreach ($script->steps as $i => $step)
                                <span class="mo-step" @if ($script->scenes[0]['step_rules'][$i] !== []) data-when="{{ implode(' ', $script->scenes[0]['step_rules'][$i]) }}" @endif>
                                    <span>@fa($i + 1)</span> <span>{{ $step }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($script->scenes as $scene)
                        @if ($scene['kind'] === 'window')
                            <div class="mo-pane" data-show="{{ $scene['show'] }}">
                                @foreach ($scene['widgets'] as $widget)
                                    @if ($widget['type'] !== 'sheet')
                                        @include('components.motion.widget', ['widget' => $widget, 'range' => $range])
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @foreach ($script->scenes as $scene)
                @foreach ($scene['widgets'] ?? [] as $widget)
                    @if ($widget['type'] === 'sheet')
                        @php
                            $sideArt = $widget['side'] !== null && view()->exists('components.art.pictures.'.$widget['side']);
                        @endphp
                        <div class="mo-sheet" data-show="{{ $sideArt ? $widget['show_before_side'] : $widget['show'] }}" data-enter="-110 20 0.7">
                            <span class="mo-sheet-title">{{ $widget['title'] }}</span>
                            @foreach ([90, 70, 84, 60, 78, 88] as $width)
                                <span class="mo-sheet-line" style="inline-size: {{ $width }}%"></span>
                            @endforeach
                            <span class="mo-sheet-foot">
                                <span data-numeric>{{ $widget['code'] }}</span>
                                <x-motion.qr />
                            </span>
                        </div>
                        <span class="mo-stamp" data-show="{{ $range($widget['stamp_at'], $scene['end']) }}" data-enter="0 0 1.4">{{ $widget['stamp'] }}</span>
                    @endif
                @endforeach

                @if ($scene['kind'] === 'phone')
                    @php
                        $phone = $scene['phone'];
                        $s = $scene['start'];
                        $hasSide = ! empty($phone['side']) && view()->exists('components.art.pictures.'.$phone['side']);
                    @endphp
                    <div class="mo-phone" data-show="{{ $scene['show'] }}" data-enter="0 24 1">
                        <span class="mo-phone-title">{{ $phone['title'] }}</span>
                        @if ($phone['scan'] ?? false)
                            <span class="mo-cam">
                                <x-motion.qr />
                                <span class="mo-beam" data-show="{{ $range($s + 0.9, $s + 2.8) }}" data-enter="0 0 1"
                                      data-progress="{{ $range($s + 1, $s + 2.6) }}"></span>
                            </span>
                        @endif
                        @if (! empty($phone['code']))
                            <span class="mo-phone-code" data-numeric data-show="{{ $range($s + 2.7) }}">{{ $phone['code'] }}</span>
                        @endif
                        <span class="mo-note" data-show="{{ $range($s + 3) }}">{{ $phone['ok'] }}</span>
                        <span class="mo-kv" data-show="{{ $range($s + 3.4) }}">
                            @foreach ($phone['lines'] ?? [] as [$label, $value])
                                <span>{{ $label }}: <b>{{ \App\Support\Help\HelpText::render($value) }}</b></span>
                            @endforeach
                        </span>
                    </div>
                    @if ($hasSide)
                        <div class="mo-phone-side" data-show="{{ $range($s + 4, $scene['end']) }}" data-enter="0 0 0.96">
                            <x-art :name="$phone['side']" />
                        </div>
                    @endif
                @endif
            @endforeach

            <span class="mo-ripple" data-motion-ripple></span>
            <span class="mo-cursor" data-motion-cursor data-path="{{ json_encode($script->path) }}">
                <svg viewBox="0 0 24 24" width="22" height="22"><path d="M4 2l15 9.5-6.6 1.4 3.9 7.6-3 1.5-3.9-7.6L4 19z" stroke-width="1.4" stroke-linejoin="round"/></svg>
            </span>
        </div>
    </div>

    <div class="motion-controls">
        <x-button variant="secondary" size="sm" class="motion-play w-touch px-0" data-motion-play aria-label="پخش">
            <x-icon name="play" data-icon="play" :size="20" />
            <x-icon name="pause" data-icon="pause" :size="20" />
        </x-button>
        <x-button variant="secondary" size="sm" class="w-touch px-0" data-motion-restart aria-label="از اول">
            <x-icon name="replay" :size="20" />
        </x-button>
        <input type="range" class="motion-seek" min="0" max="1000" step="1" value="0" data-motion-seek aria-label="جای پخش">
        <span class="min-w-24 text-center text-label text-muted" data-motion-time></span>
    </div>

    @if ($script->chapters() !== [])
        <div class="motion-chapters" role="group" aria-label="صحنه‌ها">
            @foreach ($script->chapters() as $chapter)
                <x-button variant="secondary" size="sm" class="motion-chapter" data-motion-chapter="{{ $chapter['index'] }}">
                    {{ $chapter['label'] }}
                </x-button>
            @endforeach
        </div>
    @endif

    <p class="motion-caption text-copy" data-motion-caption aria-live="polite"></p>

    <ol class="motion-transcript list-decimal space-y-1.5 p-4 ps-9 text-copy" data-motion-scenes>
        @foreach ($script->scenes as $scene)
            <li data-start="{{ $scene['start'] }}" data-end="{{ $scene['end'] }}">{{ $scene['caption'] }}</li>
        @endforeach
    </ol>
</figure>
