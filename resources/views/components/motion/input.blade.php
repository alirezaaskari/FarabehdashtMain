{{-- فیلد صحنه: یا از پیش پر است، یا با data-type در صحنه تایپ می‌شود. مقدار داخل [[…]] لاتین و چپ‌به‌راست است. --}}
@if (isset($field['type_at']))
    @php
        $latin = preg_match('/^\[\[(.+)\]\]$/u', (string) $field['value'], $match) === 1;
        $text = $latin ? $match[1] : (string) $field['value'];
    @endphp
    <span class="{{ $class }}" data-motion-target="{{ $target }}"
          data-type="{{ $field['type_at'] }}" data-text="{{ $text }}" data-focus="{{ $field['focus_at'] }}"><span data-motion-value @if ($latin) data-numeric @endif></span></span>
@else
    <span class="{{ $class }}" data-motion-target="{{ $target }}">{{ \App\Support\Help\HelpText::render($field['value'] ?? '') }}</span>
@endif
