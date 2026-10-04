@props(['tool', 'submitted' => [], 'fieldErrors' => [], 'sources' => true, 'start' => 'first'])

{{--
    فرم ابزار، ساخته‌شده از قرارداد ورودی خود فرمول.
    هیچ فیلدی دستی تعریف نمی‌شود: برچسب، واحد و بازه مجاز از موتور محاسبات
    می‌آیند، و ابزار فقط راهنمای فارسی و مقدار پیش‌فرض را اضافه می‌کند. یعنی
    افزودن یک ورودی به فرمول، خودبه‌خود فرم را هم به‌روز می‌کند.

    زیر هر ورودی «این مقدار را از کجا بیاورم؟» می‌آید؛ حالت میدانی آن را
    نمی‌خواهد (`sources` خاموش) تا فرم کوتاه بماند.
--}}

@php
    $inputs = $tool->formula->inputs();
    $steps = $tool->definition->steps;
@endphp

@if ($steps === [])
    @foreach ($inputs as $key => $input)
        <x-tools::measurement-input :tool="$tool" :key="$key" :input="$input"
                                    :submitted="$submitted" :fieldErrors="$fieldErrors" :sources="$sources" />
    @endforeach
@else
    {{-- فرم گام‌به‌گام (روش‌های پوسچر): بی‌جاوااسکریپت همه گام‌ها زیر هم‌اند؛
         resources/js/tool-steps.js یکی‌یکی نشانشان می‌دهد. سبکش جدا بار می‌شود
         تا بودجه CSS همه صفحه‌ها (DEC-34) دست نخورد. --}}
    @vite('resources/css/tool-steps.css')

    <div class="flex flex-col gap-5" data-tool-steps data-steps-start="{{ $start }}">
        <div class="tool-steps-progress" data-steps-progress hidden aria-hidden="true">
            @foreach ($steps as $step)
                <span></span>
            @endforeach
        </div>

        @foreach ($steps as $step)
            <fieldset class="tool-step" data-tool-step>
                <legend class="tool-step-title" tabindex="-1">
                    <span class="text-note font-semibold text-muted">گام @fa($loop->iteration) از @fa($loop->count)</span>
                    <span class="block text-h4 text-ink">{{ $step['title'] }}</span>
                </legend>

                <div class="flex flex-col gap-5">
                    @foreach ($step['inputs'] as $key)
                        <x-tools::measurement-input :tool="$tool" :key="$key" :input="$inputs[$key]" cards
                                                    :submitted="$submitted" :fieldErrors="$fieldErrors" :sources="$sources" />
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="tool-steps-nav" data-steps-nav hidden>
            <x-button variant="secondary" icon="back" data-steps-prev>گام قبل</x-button>
            <x-button variant="primary" data-steps-next>گام بعد <x-icon name="forward" :size="18" /></x-button>
        </div>

        {{-- امتیاز زنده با نسخه JS همان رابطه؛ فقط وقتی همه گزینه‌ها انتخاب شده‌اند. --}}
        <p class="tool-steps-score text-note" data-steps-score hidden aria-live="polite"></p>
    </div>
@endif
