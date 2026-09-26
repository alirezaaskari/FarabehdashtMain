@php
    use Illuminate\Support\Facades\Route;
@endphp

<x-layouts.public title="ثبت‌نام موفق" description="ثبت‌نام شما در دوره با موفقیت پرداخت شد." active="courses">

    <x-page-header art="courses-checkout-success" title="ثبت‌نام موفق" lede="می‌توانید همین حالا یادگیری را شروع کنید." />

    <x-card size="lg" class="mt-6">
        <x-alert tone="success" title="{{ $enrollment->course->title }}">
            کد پیگیری: <span dir="ltr" data-numeric>{{ $enrollment->uuid }}</span>
        </x-alert>

        @if (Route::has('courses.learn'))
            <div class="mt-6">
                <x-button :href="route('courses.learn', $enrollment->course)" variant="primary">
                    شروع یادگیری
                </x-button>
            </div>
        @endif
    </x-card>

</x-layouts.public>
