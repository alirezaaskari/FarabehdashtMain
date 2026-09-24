@php
    use App\Support\JalaliDate;
@endphp

<x-layouts.public title="اشتراک فعال شد" description="پرداخت شما تأیید و اشتراک فعال شد." active="pro">

    <x-page-header title="اشتراک شما فعال شد" lede="پرداخت تأیید شد." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="success" title="اشتراک حرفه‌ای فعال است">
            کد پیگیری: <span dir="ltr" data-numeric>{{ $period->uuid }}</span>
        </x-alert>

        <p class="mt-6 text-copy text-muted">
            تا {{ $period->ends_at === null ? '—' : JalaliDate::long($period->ends_at) }} فعال است.
        </p>

        <x-button :href="route('monetization.plans')" variant="secondary" class="mt-6">صفحه اشتراک</x-button>
    </x-card>

</x-layouts.public>
