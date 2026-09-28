{{-- فیلد صحنه: یا از پیش پر است، یا با data-type در صحنه تایپ می‌شود. --}}
@if (isset($field['type_at']))
    <span class="{{ $class }}" data-motion-target="{{ $target }}"
          data-type="{{ $field['type_at'] }}" data-text="{{ $field['value'] }}" data-focus="{{ $field['focus_at'] }}"><span data-motion-value></span></span>
@else
    <span class="{{ $class }}" data-motion-target="{{ $target }}">{{ \App\Support\Help\HelpText::render($field['value'] ?? '') }}</span>
@endif
