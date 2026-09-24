@props(['current', 'report' => null])

{{--
    نوار چهار مرحله گزارش‌ساز. مرحله اول پس از ساخت پیش‌نویس بسته می‌شود:
    عوض‌کردن منبع یعنی گزارش دیگری، نه ویرایش همین.
--}}

@php
    use App\Modules\Reports\Domain\Enums\ReportStep;

    $routes = [
        ReportStep::Details->value => 'reports.details',
        ReportStep::Findings->value => 'reports.findings',
        ReportStep::Review->value => 'reports.review',
    ];
@endphp

<nav aria-label="مراحل گزارش‌ساز" class="mb-8">
    <ol class="grid list-none gap-2 sm:grid-cols-4">
        @foreach (ReportStep::cases() as $step)
            @php
                $url = $report !== null && isset($routes[$step->value]) ? route($routes[$step->value], $report->uuid) : null;
                $isCurrent = $step === $current;
            @endphp
            <li>
                @if ($url && ! $isCurrent)
                    <a href="{{ $url }}"
                       class="flex min-h-touch items-center gap-2 rounded-md border border-line bg-surface px-3 text-label
                              font-semibold text-muted no-underline hover:border-primary hover:no-underline">
                        <span>@fa($step->value)</span> <span>{{ $step->label() }}</span>
                    </a>
                @else
                    <span @if ($isCurrent) aria-current="step" @endif
                          @class([
                              'flex min-h-touch items-center gap-2 rounded-md border px-3 text-label',
                              'border-primary bg-primary-soft font-bold text-on-primary-soft' => $isCurrent,
                              'border-line bg-surface-2 font-semibold text-muted' => ! $isCurrent,
                          ])>
                        <span>@fa($step->value)</span> <span>{{ $step->label() }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
