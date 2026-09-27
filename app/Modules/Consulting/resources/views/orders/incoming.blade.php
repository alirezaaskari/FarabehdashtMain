<x-layouts.workspace art="consulting-orders-incoming"
                     title="درخواست‌های رسیده"
                     heading="درخواست‌های رسیده"
                     lede="خدمت‌هایی که از شما خریده‌اند. درخواست پرداخت‌شده را تا ۴۸ ساعت بپذیرید یا رد کنید."
                     nav="consulting-incoming"
                     help="consulting-incoming">

    @if ($orders->isEmpty())
        <x-empty-state art="empty-consulting-incoming" icon="list"
                       title="هنوز درخواستی نرسیده"
                       description="وقتی کسی یکی از خدمت‌های شما را بخرد، این‌جا می‌آید و اعلان می‌گیرید." />
    @else
        @include('consulting::orders._list', ['incoming' => true])
    @endif

</x-layouts.workspace>
