@php
    use App\Modules\Chemicals\Domain\Enums\ErrorReportTopic;

    $reportErrors = $errors->getBag('report');
    $sent = session('report_status');
@endphp

{{--
    «گزارش اشتباه»: کاربر عدد یا متن نادرست را با منبع درستش گزارش می‌کند و
    مدیر آن را در پنل با منبع اصلی مقایسه می‌کند. گزارش خودش چیزی را عوض نمی‌کند.
--}}
<section id="report" aria-labelledby="report-heading" class="mt-12 border-t border-line-strong pt-5">
    <h2 id="report-heading" class="text-h3 text-ink">اشتباهی در این صفحه دیده‌اید؟</h2>
    <p class="mt-2 max-w-[46rem] text-copy text-muted">
        بگویید کدام عدد یا جمله با منبع نمی‌خواند و اگر می‌شود، نشانی منبع درست را هم بگذارید.
        مدیر پیش از هر اصلاح، متن اصلی مرجع را دوباره می‌خواند.
    </p>

    @if ($sent)
        <x-alert tone="success" title="گزارش ثبت شد" class="mt-4">{{ $sent }}</x-alert>
    @elseif (! Route::has('chemicals.report'))
        <x-alert tone="info" class="mt-4">گزارش اشتباه فعلاً بسته است.</x-alert>
    @elseif (! auth()->check())
        <x-permission-notice class="mt-4" title="برای گزارش وارد شوید"
                             description="گزارش با حساب کاربری ثبت می‌شود تا هر کس فقط چند گزارش در روز بفرستد و مدیر بتواند گزارش‌های تکراری را تشخیص دهد.">
            <x-slot:action>
                <x-button :href="Route::has('login') ? route('login') : url('/')" variant="primary" icon="user">ورود و گزارش</x-button>
            </x-slot:action>
        </x-permission-notice>
    @else
        <details class="group mt-4 rounded-xl border border-line bg-surface" @if ($reportErrors->any()) open @endif>
            <summary class="flex min-h-touch cursor-pointer list-none items-center justify-between gap-3 px-6 py-3
                            text-label font-semibold text-ink [&::-webkit-details-marker]:hidden">
                <span class="flex items-center gap-2"><x-icon name="alert" :size="17" /> گزارش اشتباه</span>
                <span class="text-muted transition-transform group-open:rotate-180"><x-icon name="chevron-down" :size="20" /></span>
            </summary>

            <form method="POST" action="{{ route('chemicals.report', $substance->slug) }}" class="flex flex-col gap-5 border-t border-line px-6 py-5" data-validate>
                @csrf

                <div>
                    <label for="report-topic" class="mb-2 block text-label font-semibold text-ink">
                        درباره کدام بخش؟ <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">الزامی</span>
                    </label>
                    <select id="report-topic" name="topic" required
                            class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        @foreach (ErrorReportTopic::cases() as $topic)
                            <option value="{{ $topic->value }}" @selected(old('topic') === $topic->value)>{{ $topic->label() }}</option>
                        @endforeach
                    </select>
                    @if ($reportErrors->has('topic'))
                        <p class="mt-2 flex items-center gap-1.5 text-note font-semibold text-danger" role="alert">
                            <x-icon name="alert" :size="14" :stroke="2.4" /> {{ $reportErrors->first('topic') }}
                        </p>
                    @endif
                </div>

                <div>
                    <label for="report-message" class="mb-2 block text-label font-semibold text-ink">
                        چه چیزی اشتباه است؟ <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">الزامی</span>
                    </label>
                    <textarea id="report-message" name="message" rows="5" required
                              minlength="{{ (int) config('chemicals.reports.message_min', 10) }}"
                              maxlength="{{ (int) config('chemicals.reports.message_max', 2000) }}"
                              aria-describedby="report-message-hint"
                              @if ($reportErrors->has('message')) aria-invalid="true" @endif
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink aria-invalid:border-danger">{{ old('message') }}</textarea>
                    <p id="report-message-hint" class="mt-2 text-note text-muted">
                        مثلاً «حد STEL در صفحه NIOSH این ماده ۱۵۰ است، نه ۱۲۵». نام شرکت یا شماره تماس ننویسید.
                    </p>
                    @if ($reportErrors->has('message'))
                        <p class="mt-2 flex items-center gap-1.5 text-note font-semibold text-danger" role="alert">
                            <x-icon name="alert" :size="14" :stroke="2.4" /> {{ $reportErrors->first('message') }}
                        </p>
                    @endif
                </div>

                <x-field name="source_url" id="report-source" type="url" label="نشانی منبع درست (اختیاری)"
                         :value="old('source_url')" dir="ltr" data-numeric placeholder="https://"
                         :error="$reportErrors->first('source_url') ?: null"
                         hint="صفحه‌ای از مرجع رسمی که عدد درست در آن آمده است." />

                <div>
                    <x-button type="submit" variant="primary" icon="check">فرستادن گزارش</x-button>
                </div>
            </form>
        </details>
    @endif
</section>
