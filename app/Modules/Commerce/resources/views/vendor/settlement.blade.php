<x-layouts.public title="تسویه" description="موجودی قابل‌تسویه شما نزد پلتفرم.">

    <x-page-header title="تسویه" lede="این مبلغ از فروش شما نزد پلتفرم مانده و هنوز پرداخت نشده است." />

    <x-card size="lg" class="mt-8">
        <p class="text-note text-muted">مانده قابل‌تسویه</p>
        <p class="mt-1 text-stat text-ink">{{ $owed->format() }}</p>

        <x-alert tone="info" class="mt-6">
            برای درخواست واریز، با پشتیبانی تماس بگیرید. فرایند درخواست خودکار
            هنوز راه‌اندازی نشده است.
        </x-alert>
    </x-card>

</x-layouts.public>
