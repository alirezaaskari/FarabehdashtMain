<x-layouts.workspace art="consulting-orders-mine"
                     title="درخواست‌های مشاوره من"
                     heading="درخواست‌های مشاوره من"
                     lede="خدمت‌هایی که از مشاوران خریده‌اید، با وضعیت هر کدام."
                     nav="consulting-orders"
                     help="consulting-orders">

    @if ($orders->isEmpty())
        <x-empty-state art="empty-consulting-orders" icon="list"
                       title="هنوز خدمتی نخریده‌اید"
                       description="در فهرست مشاوران، صفحه هر مشاور خدمت‌هایش را با قیمت نشان می‌دهد.">
            <x-slot:action>
                <x-button :href="route('consulting.index')" variant="primary">فهرست مشاوران</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        @include('consulting::orders._list', ['incoming' => false])
    @endif

</x-layouts.workspace>
