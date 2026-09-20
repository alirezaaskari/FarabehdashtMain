@props(['tool', 'submitted' => [], 'fieldErrors' => []])

{{--
    فرم ابزار، ساخته‌شده از قرارداد ورودی خود فرمول.
    هیچ فیلدی دستی تعریف نمی‌شود: برچسب، واحد و بازه مجاز از موتور محاسبات
    می‌آیند، و ابزار فقط راهنمای فارسی و مقدار پیش‌فرض را اضافه می‌کند. یعنی
    افزودن یک ورودی به فرمول، خودبه‌خود فرم را هم به‌روز می‌کند.
--}}

@php
    use App\Modules\Tools\Services\MeasurementNumber;

    $definition = $tool->definition;
@endphp

@foreach ($tool->formula->inputs() as $key => $input)
    @php
        $error = $fieldErrors[$key][0] ?? null;
        $hint = $definition->hintFor($key);
        $old = $submitted[$key] ?? null;
    @endphp

    @if ($input->list)
        @php
            $values = is_array($old) ? array_values($old) : [];
            $rows = max($definition->rows, count($values));
        @endphp

        <fieldset class="rounded-lg border border-line p-4"
                  @if ($error) aria-invalid="true" aria-describedby="{{ $key }}-error" @endif>
            <legend class="px-1 text-sm font-bold text-ink">
                {{ $input->label }}
                <span class="text-muted" dir="ltr" data-numeric>({{ $input->unit->symbol() }})</span>
            </legend>

            @if ($hint)
                <p class="mb-3 text-xs text-muted">{{ $hint }}</p>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                @for ($row = 0; $row < $rows; $row++)
                    <label class="flex items-center gap-2">
                        <span class="w-10 shrink-0 text-xs font-semibold text-muted">
                            @fa($row + 1)
                        </span>
                        <input type="text"
                               inputmode="decimal"
                               data-numeric
                               name="{{ $key }}[]"
                               value="{{ $values[$row] ?? '' }}"
                               aria-label="{{ $input->label }} — ردیف {{ $row + 1 }}"
                               class="h-field w-full rounded-md border bg-surface px-3 text-base text-ink
                                      {{ $error ? 'border-danger border-2' : 'border-line-strong' }}">
                    </label>
                @endfor
            </div>

            <p class="mt-3 text-xs text-muted">
                ردیف‌های خالی نادیده گرفته می‌شوند. برای افزودن ردیف بیشتر، مقدارها را
                ذخیره کنید و دوباره باز کنید.
            </p>

            @if ($error)
                <p id="{{ $key }}-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-danger">
                    <x-icon name="alert" :size="14" :stroke="2.4" />
                    {{ $error }}
                </p>
            @endif
        </fieldset>
    @else
        @php
            $default = $definition->defaultFor($key);
            $value = $old ?? ($default !== null ? MeasurementNumber::format($default) : '');
        @endphp

        <x-field :name="$key"
                 :label="$input->label"
                 :value="$value"
                 :hint="$hint"
                 :error="$error"
                 :suffix="$input->unit->dimensionless() ? null : $input->unit->symbol()"
                 numeric
                 required />
    @endif
@endforeach
