@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="scene.instruments" title="دفترچه تجهیزات"
                     heading="دفترچه تجهیزات"
                     lede="تجهیز را یک‌بار ثبت کنید؛ مشخصاتش هنگام ساخت گزارش خودکار درج می‌شود."
                     active="tools"
                     nav="equipment" help="equipment">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['پروژه‌های اندازه‌گیری', route('projects.index')],
            ['دفترچه تجهیزات', null],
        ]" />
    </x-slot:breadcrumb>

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[1.5fr_1fr]">

        <div class="min-w-0">
            @if ($equipment->isEmpty())
                <x-empty-state icon="badge"
                               title="هنوز تجهیزی ثبت نکرده‌اید"
                               description="با فرم کناری اولین دستگاهتان را اضافه کنید؛ بدون تجهیز، هشدار کالیبراسیون پیش از گزارش کار نمی‌کند." />
            @else
                <x-data-table :headers="['دستگاه', 'شناسه', 'کلاس دقت', 'مرجع کالیبراسیون', 'اعتبار تا', 'وضعیت']"
                              caption="فهرست تجهیزات ثبت‌شده و وضعیت کالیبراسیون">
                    @foreach ($equipment as $item)
                        @php $status = $item->calibrationStatus(); @endphp
                        <tr>
                            <td class="font-semibold text-ink">{{ $item->name }}</td>
                            <td><span dir="ltr" data-numeric>{{ $item->identification() }}</span></td>
                            <td>{{ $item->accuracy_class ?? '—' }}</td>
                            <td>{{ $item->calibration_reference ?? '—' }}</td>
                            <td>
                                {{ $item->calibration_valid_until
                                    ? JalaliDate::short($item->calibration_valid_until)
                                    : 'ثبت نشده' }}
                            </td>
                            <td><x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge></td>
                        </tr>
                    @endforeach

                    <x-slot:footnote>
                        «ثبت نشده» با «معتبر» یکی نیست: تجهیز بدون تاریخ اعتبار، پیش از صدور گزارش هشدار می‌دهد.
                    </x-slot:footnote>
                </x-data-table>
            @endif

            <x-disclaimer class="mt-6">
                ثبت تجهیز در فرابهداشت جایگزین گواهی کالیبراسیون رسمی نیست و صحت داده‌های
                واردشده بر عهده کاربر است.
            </x-disclaimer>
        </div>

        <x-card title="افزودن تجهیز">
            <form method="POST" action="{{ route('projects.equipment.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf

                <x-field name="name" label="نام دستگاه" required :error="$errors->first('name')" />
                <x-field name="manufacturer" label="سازنده" :error="$errors->first('manufacturer')" />
                <x-field name="model" label="مدل" :error="$errors->first('model')" />
                <x-field name="serial_number" label="شماره سریال" numeric :error="$errors->first('serial_number')" />
                <x-field name="accuracy_class" label="کلاس دقت" :error="$errors->first('accuracy_class')" />
                <x-field name="calibration_reference" label="مرجع کالیبراسیون" :error="$errors->first('calibration_reference')" />
                <x-field name="calibrated_on" label="تاریخ کالیبراسیون" type="date" :error="$errors->first('calibrated_on')" />
                <x-field name="calibration_valid_until" label="اعتبار تا" type="date"
                         :error="$errors->first('calibration_valid_until')"
                         hint="برای هشدار پیش از صدور گزارش لازم است." />

                <div class="sm:col-span-2">
                    <x-button type="submit" variant="primary" icon="plus" block>ثبت تجهیز</x-button>
                </div>
            </form>
        </x-card>

    </div>

</x-layouts.workspace>
