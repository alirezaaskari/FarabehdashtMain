<x-layouts.public title="پرداخت ناموفق" description="پرداخت ثبت‌نام دوره انجام نشد." active="courses">

    <x-page-header art="character.shrug" title="پرداخت ناموفق" lede="نگران نباشید؛ مبلغی از حساب شما کسر نشده است." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="error" title="ثبت‌نام پرداخت نشد">
            {{ $reason ?? 'درگاه پرداخت را رد کرد یا فرایند ناتمام ماند.' }}
        </x-alert>
    </x-card>

</x-layouts.public>
