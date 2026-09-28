{{--
    یک جزء صحنه آموزش متحرک. زمان‌ها را MotionScript پیش‌تر در خود جزء نوشته است؛
    این‌جا فقط به نشانه‌های data-* تبدیل می‌شوند (قرارداد در resources/js/motion.js).
--}}

@php
    use App\Support\Help\HelpText;

    $w = $widget;
    $show = $range($w['at']);
@endphp

@switch($w['type'])
    @case('heading')
        <div class="flex flex-col" data-show="{{ $show }}">
            <span class="mo-h">{{ $w['text'] }}</span>
            @if (! empty($w['sub']))
                <span class="mo-sub mt-1.5">{{ $w['sub'] }}</span>
            @endif
        </div>
        @break

    @case('text')
        <p class="mo-text" data-show="{{ $show }}">{{ HelpText::render($w['text']) }}</p>
        @break

    @case('choice')
        @php
            $marker = $w['marker'] ?? 'radio';
            $pick = $w['pick'] ?? null;
        @endphp
        <div class="mo-rows" data-show="{{ $show }}">
            @foreach ($w['items'] as $i => $item)
                @php
                    $rules = [];
                    if ($pick !== null && $i === $pick) {
                        $rules[] = 'is-on@'.$w['picked_at'];
                    } elseif ($pick !== null && $pick !== 0 && $i === 0 && $marker === 'radio') {
                        $rules[] = 'is-on@'.$w['at'].'-'.$w['picked_at'];
                    }
                @endphp
                <div class="mo-row" data-motion-target="{{ $w['id'] }}-{{ $i }}" @if ($rules) data-when="{{ implode(' ', $rules) }}" @endif>
                    @if ($marker === 'radio')
                        <span class="mo-radio"></span>
                    @elseif ($marker === 'check')
                        <span class="mo-box"></span>
                    @endif
                    <span class="mo-row-title">{{ HelpText::render($item[0]) }}</span>
                    @if (isset($item[1]))
                        <span class="mo-row-meta">{{ HelpText::render($item[1]) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        @break

    @case('fields')
        <div class="mo-fields" data-show="{{ $show }}">
            @foreach ($w['items'] as $i => $field)
                <div @class(['mo-field', 'is-full' => $field['full'] ?? false])>
                    <span class="mo-label">{{ $field['label'] }}</span>
                    @include('components.motion.input', ['field' => $field, 'target' => $w['id'].'-'.$i, 'class' => 'mo-input'])
                </div>
            @endforeach
        </div>
        @break

    @case('search')
    @case('textarea')
        <div class="mo-field" data-show="{{ $show }}">
            @if (! empty($w['label']))
                <span class="mo-label">{{ $w['label'] }}</span>
            @endif
            @include('components.motion.input', [
                'field' => $w,
                'target' => $w['id'].'-0',
                'class' => $w['type'] === 'search' ? 'mo-input is-search' : 'mo-area',
            ])
        </div>
        @break

    @case('toggle')
    @case('check')
        <div class="{{ $w['type'] === 'toggle' ? 'mo-toggle-row' : 'mo-check-row' }}" data-show="{{ $show }}"
             data-when="{{ 'is-on@'.$w['on_at'] }}">
            <span class="{{ $w['type'] === 'toggle' ? 'mo-toggle' : 'mo-box' }}" data-motion-target="{{ $w['id'] }}"></span>
            <span>{{ $w['label'] }}</span>
        </div>
        @break

    @case('button')
        <span @class(['mo-button', 'is-secondary' => ($w['variant'] ?? null) === 'secondary'])
              data-show="{{ $show }}" data-motion-target="{{ $w['id'] }}"
              data-when="{{ 'is-on@'.$w['on_at'].'-'.($w['on_at'] + 0.3) }}">{{ $w['label'] }}</span>
        @break

    @case('table')
        <div class="mo-table" data-show="{{ $show }}">
            <div class="mo-tr is-head">
                @foreach ($w['head'] as $cell)
                    <span>{{ $cell }}</span>
                @endforeach
            </div>
            @foreach ($w['rows'] as $i => $row)
                <div class="mo-tr" data-show="{{ $range($w['at'] + 0.25 * $i) }}">
                    @foreach ($row as $cell)
                        <span>{{ HelpText::render($cell) }}</span>
                    @endforeach
                </div>
            @endforeach
        </div>
        @break

    @case('stats')
        <div class="mo-stats" data-show="{{ $show }}">
            @foreach ($w['items'] as $i => [$label, $value])
                <div class="mo-stat">
                    <div class="mo-stat-label">{{ $label }}</div>
                    <div class="mo-stat-value" data-count="{{ $w['at'] + 0.15 * $i }}" data-value="{{ $value }}">@fa($value)</div>
                </div>
            @endforeach
        </div>
        @break

    @case('note')
    @case('badge')
        <span @class(['mo-'.$w['type'], 'is-caution' => ($w['tone'] ?? null) === 'caution'])
              data-show="{{ $show }}">{{ HelpText::render($w['text']) }}</span>
        @break

    @case('track')
        <div class="mo-track" data-show="{{ $show }}">
            @foreach ($w['items'] as $i => $item)
                <span class="mo-track-item" data-when="{{ 'is-on@'.($w['at'] + 0.9 * $i) }}">{{ $item }}</span>
            @endforeach
        </div>
        @break

    @case('upload')
        <div class="mo-upload" data-show="{{ $show }}" data-motion-target="{{ $w['id'] }}"
             data-when="{{ 'is-on@'.explode(' ', $w['progress_at'])[0] }}">
            <span>{{ $w['label'] }}</span>
            <span class="mo-upload-file" data-numeric data-show="{{ $w['progress_at'] }}" data-enter="0 0 1">{{ $w['file'] }}</span>
            <span class="mo-progress" data-progress="{{ $w['progress_at'] }}"></span>
            <span class="mo-note" data-show="{{ $range($w['done_at']) }}">{{ HelpText::render($w['result']) }}</span>
        </div>
        @break
@endswitch
