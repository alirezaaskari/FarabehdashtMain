@props(['tool', 'submitted' => [], 'fieldErrors' => [], 'sources' => true])

{{--
    فرم ابزار، ساخته‌شده از قرارداد ورودی خود فرمول.
    هیچ فیلدی دستی تعریف نمی‌شود: برچسب، واحد و بازه مجاز از موتور محاسبات
    می‌آیند، و ابزار فقط راهنمای فارسی و مقدار پیش‌فرض را اضافه می‌کند. یعنی
    افزودن یک ورودی به فرمول، خودبه‌خود فرم را هم به‌روز می‌کند.

    زیر هر ورودی «این مقدار را از کجا بیاورم؟» می‌آید؛ حالت میدانی آن را
    نمی‌خواهد (`sources` خاموش) تا فرم کوتاه بماند.
--}}

@php
    use App\Support\Measurement\MeasurementNumber;

    $definition = $tool->definition;
@endphp

@foreach ($tool->formula->inputs() as $key => $input)
    @php
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
@endforeach
