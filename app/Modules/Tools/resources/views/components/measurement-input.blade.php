@props(['tool', 'key', 'input', 'submitted' => [], 'fieldErrors' => [], 'sources' => true, 'cards' => false])

{{--
    یک ورودی فرم ابزار: فهرستی، کلید روشن/خاموش، گزینه (فهرست یا کارت)، یا عدد.
    برچسب، واحد و بازه از قرارداد خود فرمول می‌آید؛ ابزار فقط راهنما، گزینه و
    شکل را اضافه می‌کند.

    کارت‌ها (`cards`) در فرم گام‌به‌گام: هر گزینه رادیوی واقعی با برچسب کامل، و
    برای بازه زاویه شکل خطی همان بازه کنارش.
--}}

@php
    use App\Support\Measurement\MeasurementNumber;

    $definition = $tool->definition;
    $error = $fieldErrors[$key][0] ?? null;
    $hint = $definition->hintFor($key);
    $old = $submitted[$key] ?? null;
    $source = $sources ? $definition->sourceFor($key) : null;
@endphp

@if ($input->list)
    @php
        $values = is_array($old) ? array_values($old) : [];
        $rows = max($definition->rows, count($values));
    @endphp

    <fieldset class="border-0 p-0"
              @if ($error) aria-invalid="true" aria-describedby="{{ $key }}-error" @endif>
        <legend class="mb-2 text-label font-semibold text-ink">
            {{ $input->label }}
            <span class="text-muted" dir="ltr" data-numeric>({{ $input->unit->symbol() }})</span>
        </legend>

        @if ($hint)
            <p class="mb-3 text-note text-muted">{{ $hint }}</p>
        @endif

        @if ($source)
            <x-tools::input-source :key="$key" :text="$source" class="mb-3" />
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            @for ($row = 0; $row < $rows; $row++)
                <label class="flex items-center gap-2">
                    <span class="w-10 shrink-0 text-note font-semibold text-muted">
                        @fa($row + 1)
                    </span>
                    <input type="text"
                           inputmode="decimal"
                           data-numeric
                           name="{{ $key }}[]"
                           value="{{ $values[$row] ?? '' }}"
                           aria-label="{{ $input->label }} — ردیف {{ $row + 1 }}"
                           class="h-field w-full rounded-md border bg-surface px-3 text-control text-ink
                                  {{ $error ? 'border-danger border-2' : 'border-line-strong' }}">
                </label>
            @endfor
        </div>

        <p class="mt-3 text-note text-muted">
            ردیف‌های خالی نادیده گرفته می‌شوند. برای افزودن ردیف بیشتر، مقدارها را
            ذخیره کنید و دوباره باز کنید.
        </p>

        @if ($error)
            <p id="{{ $key }}-error" class="mt-2 flex items-center gap-1.5 text-note font-semibold text-danger">
                <x-icon name="alert" :size="14" :stroke="2.4" />
                {{ $error }}
            </p>
        @endif
    </fieldset>
@elseif ($definition->isToggle($key))
    {{-- صفر و یک: فیلد پنهان صفر پیش از کلید، تا خاموش هم فرستاده شود. --}}
    @php
        $default = $definition->defaultFor($key);
        $on = (string) ($old ?? ($default !== null ? MeasurementNumber::format($default) : '0')) === '1';
    @endphp

    <div class="rounded-md border border-line px-4 py-1">
        <input type="hidden" name="{{ $key }}" value="0">
        <x-toggle :name="$key" :label="$input->label" :description="$hint" :checked="$on" />

        @if ($source)
            <x-tools::input-source :key="$key" :text="$source" />
        @endif

        @if ($error)
            <p id="{{ $key }}-error" class="mb-2 flex items-center gap-1.5 text-note font-semibold text-danger">
                <x-icon name="alert" :size="14" :stroke="2.4" />
                {{ $error }}
            </p>
        @endif
    </div>
@elseif ($cards && ($choices = $definition->choicesFor($key)))
    @php
        $default = $definition->defaultFor($key);
        $selected = (string) ($old ?? ($default !== null ? MeasurementNumber::format($default) : ''));
        $figure = $definition->figureFor($key);
    @endphp

    <fieldset class="border-0 p-0"
              @if ($hint || $error) aria-describedby="{{ collect([$hint ? $key.'-hint' : null, $error ? $key.'-error' : null])->filter()->implode(' ') }}" @endif>
        <legend class="mb-2 text-label font-semibold text-ink">
            {{ $input->label }}
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">الزامی</span>
        </legend>

        @if ($hint)
            <p id="{{ $key }}-hint" class="mb-3 text-note text-muted">{{ $hint }}</p>
        @endif

        <div class="tool-choices">
            @foreach ($choices as $code => $choice)
                <label class="tool-choice">
                    <input type="radio" name="{{ $key }}" value="{{ $code }}" @checked($selected === (string) $code)
                           @if ($loop->first) required @endif
                           @if ($error) aria-invalid="true" @endif>
                    @if ($figure && isset($figure['ranges'][$code]))
                        <x-tools::angle-figure :segment="$figure['segment']" :ranges="$figure['ranges'][$code]" />
                    @endif
                    <span class="text-label">{{ $choice }}</span>
                </label>
            @endforeach
        </div>

        @if ($source)
            <x-tools::input-source :key="$key" :text="$source" class="mt-2" />
        @endif

        @if ($error)
            <p id="{{ $key }}-error" class="mt-1.5 flex items-center gap-1.5 text-note font-semibold text-danger">
                <x-icon name="alert" :size="14" :stroke="2.4" />
                {{ $error }}
            </p>
        @endif
    </fieldset>
@elseif ($choices = $definition->choicesFor($key))
    {{-- ورودی کددار (مثل کیفیت دستگیره): موتور عدد می‌خواهد، کاربر گزینه می‌بیند. --}}
    @php
        $default = $definition->defaultFor($key);
        $selected = (string) ($old ?? ($default !== null ? MeasurementNumber::format($default) : ''));
    @endphp

    <div class="w-full">
        <label for="{{ $key }}" class="mb-2 block text-label font-semibold text-ink">
            {{ $input->label }}
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">الزامی</span>
        </label>
        <select id="{{ $key }}" name="{{ $key }}" required
                @if ($error) aria-invalid="true" @endif
                @if ($hint || $error) aria-describedby="{{ collect([$hint ? $key.'-hint' : null, $error ? $key.'-error' : null])->filter()->implode(' ') }}" @endif
                class="h-field w-full rounded-md border bg-surface px-3 text-control text-ink
                       {{ $error ? 'border-danger border-2' : 'border-line-strong' }}">
            @foreach ($choices as $code => $choice)
                <option value="{{ $code }}" @selected($selected === (string) $code)>{{ $choice }}</option>
            @endforeach
        </select>

        @if ($hint)
            <p id="{{ $key }}-hint" class="mt-2 text-note text-muted">{{ $hint }}</p>
        @endif

        @if ($source)
            <x-tools::input-source :key="$key" :text="$source" />
        @endif

        @if ($error)
            <p id="{{ $key }}-error" class="mt-1.5 flex items-center gap-1.5 text-note font-semibold text-danger">
                <x-icon name="alert" :size="14" :stroke="2.4" />
                {{ $error }}
            </p>
        @endif
    </div>
@else
    @php
        $default = $definition->defaultFor($key);
        $value = $old ?? ($default !== null ? MeasurementNumber::format($default) : '');
    @endphp

    <div>
        <x-field :name="$key"
                 :label="$input->label"
                 :value="$value"
                 :hint="$hint"
                 :error="$error"
                 :suffix="$input->unit->dimensionless() ? null : $input->unit->symbol()"
                 numeric
                 required />

        @if ($source)
            <x-tools::input-source :key="$key" :text="$source" />
        @endif
    </div>
@endif
