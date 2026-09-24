{{--
    محاسبه سریع ppm به mg/m³ کنار عنوان صفحه اصلی، مثل پروتوتایپ.

    محاسبه همان مسیر ابزار کامل است و این‌جا فقط فرم کوتاهش: نتیجه در صفحه
    ابزار و با موتور محاسبه می‌آید، نه با جاوااسکریپت این قالب؛ فرمول فقط
    یک جا زندگی می‌کند. `$quickTool` و `$defaults` را composer ماژول ابزارها
    می‌دهد؛ ابزار خاموش یعنی فرم نیست.
--}}

@if ($quickTool !== null && Route::has('tools.calculate'))
    <form method="POST" action="{{ route('tools.calculate', $quickTool) }}#result"
          class="rounded-xl border border-line bg-surface-2 px-6 py-7 md:px-7.5">
        @csrf

        @foreach ($defaults as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <h2 class="text-copy font-extrabold text-ink">
            محاسبه سریع: تبدیل <span data-numeric>ppm → mg/m³</span>
        </h2>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <x-field name="concentration" label="غلظت (ppm)" :numeric="true" required />
            <x-field name="molecular_weight" label="جرم مولکولی (g/mol)" :numeric="true" required />
        </div>

        <x-button type="submit" size="lg" :block="true" class="mt-4">محاسبه کن</x-button>

        <p class="mt-4 text-note text-muted">
            در دمای @fa($defaults['temperature'] ?? 25) درجه و فشار {{ \App\Support\PersianNumber::decimal((float) ($defaults['pressure'] ?? 101.325), 3) }} کیلوپاسکال. این خروجی جایگزین قضاوت
            کارشناسی نیست و ادعای انطباق قانونی قطعی ندارد.
        </p>
    </form>
@endif
