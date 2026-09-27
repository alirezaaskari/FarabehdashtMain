<x-layouts.public title="اشتراک تیم فعال شد" description="پرداخت شما تأیید و اشتراک تیم فعال شد." active="pro">

    <x-page-header art="team-paid" title="اشتراک تیم فعال شد" lede="پرداخت تأیید شد. حالا اعضا را با شماره موبایل دعوت کنید." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="success" :title="\App\Support\PersianNumber::format($period->seats).' صندلی فعال است'">
            کد پیگیری: <span dir="ltr" data-numeric>{{ $period->uuid }}</span>
        </x-alert>

        <p class="mt-6 text-copy text-muted">
            تا {{ $period->ends_at === null ? '—' : \App\Support\JalaliDate::long($period->ends_at) }} فعال است.
        </p>

        <x-button :href="route('monetization.team')" variant="primary" class="mt-6">دعوت اعضا</x-button>
    </x-card>

</x-layouts.public>
