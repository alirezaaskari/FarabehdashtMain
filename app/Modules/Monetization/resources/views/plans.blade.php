@php
    use App\Support\JalaliDate;
@endphp

<x-layouts.public title="اشتراک حرفه‌ای"
                  description="ذخیره نامحدود محاسبه و پروژه اندازه‌گیری، با اشتراک ماهانه یا سالانه."
                  active="pro">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['اشتراک حرفه‌ای', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="اشتراک حرفه‌ای"
                   lede="ذخیره نامحدود محاسبه، پروژه اندازه‌گیری نامحدود و تخفیف پایدار فروشگاه." />

    @if ($subscription?->isCurrent())
        <x-alert tone="success" title="اشتراک شما فعال است" class="mt-6">
            تا {{ $subscription->ends_at === null ? '—' : JalaliDate::long($subscription->ends_at) }} فعال است.
            @if ($subscription->cancelled_at !== null)
                تمدید خودکار متوقف شده و پس از این تاریخ به پلن رایگان برمی‌گردید.
            @endif
        </x-alert>
    @endif

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($plans as $plan)
            <x-card size="lg">
                <p class="text-h3 text-ink">{{ $plan->title }}</p>

                <p class="mt-4 text-stat text-ink" dir="ltr" data-numeric>{{ $plan->price()->formatWithoutUnit() }}</p>
                <p class="mt-1 text-note text-muted">تومان، هر {{ $plan->billing_cycle->label() }}</p>

                <ul class="mt-6 flex flex-col gap-2.5 text-copy text-muted">
                    <li>ذخیره نامحدود محاسبه</li>
                    <li>پروژه اندازه‌گیری نامحدود</li>
                    <li>تخفیف پایدار روی فایل‌های فروشگاه</li>
                </ul>

                @auth
                    <form method="POST" action="{{ route('monetization.checkout', $plan->slug) }}" class="mt-7">
                        @csrf
                        <x-button type="submit" variant="primary" block>
                            {{ $hasAccess ? 'تمدید این پلن' : 'خرید این پلن' }}
                        </x-button>
                    </form>
                @else
                    <x-button :href="Route::has('login') ? route('login') : '#'" variant="primary" block class="mt-7">
                        ورود و خرید
                    </x-button>
                @endauth
            </x-card>
        @endforeach
    </div>

    @if ($subscription?->isCurrent() && $subscription->cancelled_at === null)
        <form method="POST" action="{{ route('monetization.cancel') }}" class="mt-6">
            @csrf
            <x-button type="submit" variant="secondary">توقف تمدید خودکار</x-button>
        </form>
    @endif

    <x-disclaimer class="mt-10">
        اشتراک حرفه‌ای فقط دسترسی به ابزارهای این سایت است و هیچ گواهی، مدرک رسمی یا
        تأیید حرفه‌ای به همراه ندارد.
    </x-disclaimer>

</x-layouts.public>
