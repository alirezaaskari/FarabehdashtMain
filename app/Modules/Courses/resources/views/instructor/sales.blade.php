<x-layouts.public title="گزارش فروش" description="فروش دوره‌های شما.">

    <x-page-header title="گزارش فروش" lede="آخرین ۱۰۰ ثبت‌نام پرداخت‌شده، جدیدترین بالا." />

    <div class="mt-6">
        @if ($enrollments->isEmpty())
            <x-empty-state icon="wallet" title="هنوز فروشی ثبت نشده" description="با انتشار دوره، ثبت‌نام‌ها این‌جا نشان داده می‌شوند." />
        @else
            <x-data-table :headers="['دوره', 'قیمت', 'کمیسیون', 'سهم شما']" caption="ثبت‌نام‌های اخیر">
                @foreach ($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->course->title }}</td>
                        <td dir="ltr" data-numeric>{{ $enrollment->price()->format() }}</td>
                        <td dir="ltr" data-numeric>{{ $enrollment->commission()->format() }}</td>
                        <td dir="ltr" data-numeric>{{ $enrollment->instructorAmount()->format() }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </div>

</x-layouts.public>
