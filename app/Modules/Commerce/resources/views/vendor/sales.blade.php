<x-layouts.public title="گزارش فروش" description="فروش محصولات شما در فروشگاه.">

    <x-page-header title="گزارش فروش" lede="آخرین ۱۰۰ ردیف فروش، جدیدترین بالا." />

    <div class="mt-6">
        @if ($items->isEmpty())
            <x-empty-state icon="wallet" title="هنوز فروشی ثبت نشده" description="با انتشار محصول، فروش‌ها این‌جا نشان داده می‌شوند." />
        @else
            <x-data-table :headers="['محصول', 'قیمت', 'کمیسیون', 'سهم شما', 'وضعیت سفارش']" caption="فروش‌های اخیر">
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->product->title }}</td>
                        <td dir="ltr" data-numeric>{{ $item->unitPrice()->format() }}</td>
                        <td dir="ltr" data-numeric>{{ $item->commission()->format() }}</td>
                        <td dir="ltr" data-numeric>{{ $item->vendorAmount()->format() }}</td>
                        <td><x-badge :tone="$item->order->status->tone()">{{ $item->order->status->label() }}</x-badge></td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </div>

</x-layouts.public>
