@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="دفترچه تجهیزات" heading="دفترچه تجهیزات">

    <div class="max-w-4xl">

        <nav aria-label="مسیر صفحه" class="mb-4 text-sm">
            <a href="{{ route('projects.index') }}"
               class="inline-flex h-touch items-center text-primary">پروژه‌ها</a>
            <span class="text-muted"> / دفترچه تجهیزات</span>
        </nav>

        <p class="mt-1.5 text-sm text-muted">
            تجهیز را یک‌بار ثبت کنید؛ مشخصاتش هنگام ساخت گزارش خودکار درج می‌شود.
        </p>

        @if (session('status'))
            <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert>
        @endif

        @if ($equipment->isEmpty())
            <x-empty-state class="mt-8"
                           icon="badge"
                           title="هنوز تجهیزی ثبت نکرده‌اید"
                           description="با فرم پایین اولین دستگاهتان را اضافه کنید." />
        @else
            <ul class="mt-8 flex flex-col gap-3">
                @foreach ($equipment as $item)
                    @php $status = $item->calibrationStatus(); @endphp
                    <li class="rounded-xl border border-line bg-surface p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <span class="block font-bold text-ink">{{ $item->name }}</span>
                                <span class="mt-1 block text-xs text-muted" dir="ltr" data-numeric>
                                    {{ $item->identification() }}
                                </span>
                            </div>

                            <x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge>
                        </div>

                        <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                            <div>
                                <dt class="text-muted">اعتبار تا</dt>
                                <dd class="font-semibold text-ink">
                                    {{ $item->calibration_valid_until ? JalaliDate::short($item->calibration_valid_until) : 'ثبت نشده' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted">مرجع کالیبراسیون</dt>
                                <dd class="font-semibold text-ink">{{ $item->calibration_reference ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">کلاس دقت</dt>
                                <dd class="font-semibold text-ink">{{ $item->accuracy_class ?? '—' }}</dd>
                            </div>
                        </dl>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-card class="mt-8" title="افزودن تجهیز" :level="2">
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
                    <x-button type="submit" variant="primary" icon="plus">ثبت تجهیز</x-button>
                </div>
            </form>
        </x-card>

        <x-disclaimer class="mt-8" size="md">
            ثبت تجهیز در فرابهداشت جایگزین گواهی کالیبراسیون رسمی نیست و صحت داده‌های
            واردشده بر عهده کاربر است.
        </x-disclaimer>

    </div>

</x-layouts.workspace>
