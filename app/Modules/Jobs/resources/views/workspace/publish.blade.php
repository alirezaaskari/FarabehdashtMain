<x-layouts.workspace art="posting-publish" :title="$renewal ? 'تمدید آگهی' : 'انتشار آگهی'" :heading="$renewal ? 'تمدید آگهی' : 'انتشار آگهی'"
                     :lede="'«'.$posting->title.'» تأیید شده است. با این پرداخت '.\App\Support\PersianNumber::format($days).' روز در فهرست کاریابی می‌ماند.'"
                     nav="employer-postings" help="posting-publish">

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <x-card title="آنچه می‌گیرید" heading="text-h4">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                <li>نمایش در فهرست کاریابی، صفحه شهر و صفحه مهارت‌های آگهی.</li>
                <li>داده ساختاریافته برای جست‌وجوی شغل گوگل تا روز پایان اعتبار.</li>
                @if ($renewal)
                    <li>تمدید آگهی زنده از پایان اعتبار فعلی حساب می‌شود؛ روزی از دست نمی‌رود.</li>
                @endif
                <li>درخواست‌دادن برای کارجو رایگان است؛ مبلغ فقط انتشار آگهی است.</li>
            </ul>
        </x-card>

        <x-card title="مبلغ" heading="text-h4">
            <p class="text-h3 text-ink">{{ $price->isZero() ? 'رایگان' : $price->format() }}</p>
            <p class="mt-1 text-note text-muted">برای @fa($days) روز</p>

            <form method="POST" action="{{ route('jobs.employer.postings.pay', $posting->uuid) }}" class="mt-5">
                @csrf
                @error('payment')
                    <p class="mb-3 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
                <x-payment-method :total="$price" class="mb-4" />
                <x-button type="submit" variant="primary" block>{{ $price->isZero() ? 'انتشار رایگان' : 'پرداخت '.$price->format() }}</x-button>
            </form>
        </x-card>
    </div>

</x-layouts.workspace>
