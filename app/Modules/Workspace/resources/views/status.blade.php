@php
    use App\Support\JalaliDate;

    // رنگ هر خانه نوار — کلاس معنایی، نه رنگ هگز.
    $bar = [
        'operational' => 'bg-primary',
        'maintenance' => 'bg-line-strong',
        'degraded' => 'bg-caution',
        'outage' => 'bg-danger',
    ];
@endphp

<x-layouts.public title="وضعیت سرویس"
                  description="وضعیت لحظه‌ای سایت، ورود، پرداخت، دانلود، گزارش‌ساز و جست‌وجوی فرابهداشت."
                  noindex>

    <x-page-header title="وضعیت سرویس"
                   lede="اگر بخشی از فرابهداشت درست کار نمی‌کند، پیش از تماس با پشتیبانی این‌جا را ببینید." />

    @if (session('status'))
        <div class="mt-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    <div class="mt-8">
        <x-alert :tone="$overall->value === 'operational' ? 'success' : ($overall->value === 'outage' ? 'error' : 'caution')"
                 :title="$overall->value === 'operational' ? 'همه سرویس‌ها برقرارند' : 'بخشی از سرویس‌ها اختلال دارند'">
            {{ $overall->value === 'operational'
                ? 'هیچ اختلال یا نگهداری فعالی ثبت نشده است.'
                : 'جزئیات هر مورد پایین همین صفحه آمده است.' }}
        </x-alert>
    </div>

    @foreach ($open as $incident)
        <x-card class="mt-6" :title="$incident->title" :subtitle="$incident->service->label().' · از '.JalaliDate::longWithTime($incident->started_at)">
            <x-badge :tone="$incident->state->tone()">{{ $incident->state->label() }}</x-badge>

            @if ($incident->body)
                <p class="mt-3 text-copy text-ink">{{ $incident->body }}</p>
            @endif

            <div class="mt-4">
                @auth
                    @if (in_array($incident->getKey(), $subscribed, true))
                        <p class="text-note text-muted">وقتی رفع شود، در اعلان‌های میزکار خبرتان می‌کنیم.</p>
                    @else
                        <form method="POST" action="{{ route('workspace.status.subscribe', $incident->uuid) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm" icon="bell">خبرم کن وقتی رفع شد</x-button>
                        </form>
                    @endif
                @else
                    <p class="text-note text-muted">برای اینکه هنگام رفع اختلال خبرتان کنیم، وارد حساب شوید.</p>
                @endauth
            </div>
        </x-card>
    @endforeach

    <x-card class="mt-8" title="سرویس‌ها" :subtitle="'نوار هر سرویس، '.\App\Support\PersianDigits::from($days).' روز اخیر را نشان می‌دهد؛ امروز سمت چپ است.'">
        <ul class="flex flex-col divide-y divide-line-soft">
            @foreach ($services as $service)
                @php $state = $current[$service->value]; @endphp
                <li class="py-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-label font-bold text-ink">{{ $service->label() }}</span>
                        <x-badge :tone="$state->tone()">{{ $state->label() }}</x-badge>
                    </div>

                    <ol class="mt-3 flex h-8 gap-0.5" aria-label="سابقه {{ $service->label() }}">
                        @foreach ($history[$service->value] as $day)
                            <li class="h-full min-w-0 grow rounded-sm {{ $bar[$day['state']->value] }}"
                                title="{{ JalaliDate::long($day['date']) }}: {{ $day['state']->label() }}">
                                <span class="sr-only">{{ JalaliDate::long($day['date']) }}: {{ $day['state']->label() }}</span>
                            </li>
                        @endforeach
                    </ol>
                </li>
            @endforeach
        </ul>
    </x-card>

    @if ($upcoming->isNotEmpty())
        <x-card class="mt-8" title="نگهداری پیش‌رو">
            <ul class="flex flex-col gap-4">
                @foreach ($upcoming as $incident)
                    <li>
                        <p class="text-label font-bold text-ink">{{ $incident->title }}</p>
                        <p class="mt-1 text-note text-muted">{{ $incident->service->label() }} · {{ JalaliDate::longWithTime($incident->started_at) }}</p>
                        @if ($incident->body)
                            <p class="mt-2 text-copy text-ink">{{ $incident->body }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card class="mt-8" title="سابقه رویدادها">
        @if ($resolved->isEmpty())
            <p class="text-copy text-muted">در این بازه رویدادی ثبت نشده است.</p>
        @else
            <ul class="flex flex-col divide-y divide-line-soft">
                @foreach ($resolved as $incident)
                    <li class="py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-label font-bold text-ink">{{ $incident->title }}</span>
                            <x-badge :tone="$incident->state->tone()">{{ $incident->state->label() }}</x-badge>
                        </div>
                        <p class="mt-1 text-note text-muted">
                            {{ $incident->service->label() }} ·
                            {{ JalaliDate::longWithTime($incident->started_at) }} تا {{ JalaliDate::longWithTime($incident->resolved_at) }}
                        </p>
                        @if ($incident->resolution)
                            <p class="mt-2 text-copy text-ink">{{ $incident->resolution }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

</x-layouts.public>
