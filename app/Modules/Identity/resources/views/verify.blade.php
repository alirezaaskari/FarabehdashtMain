<x-layouts.base title="کد تأیید" theme="light" noindex>
    <main id="main" class="mx-auto flex min-h-screen max-w-lg items-center justify-center p-6 lg:p-14">
        <x-card class="w-full" title="کد تأیید" :level="1">
            <x-slot:subtitle>
                کد @fa($codeLength) رقمی ارسال‌شده به <span dir="ltr" data-numeric>{{ $destination }}</span> را وارد کنید.
            </x-slot:subtitle>

            @if (session('status'))
                <div class="mb-5"><x-alert tone="success">{{ session('status') }}</x-alert></div>
            @endif

            <form method="POST" action="{{ route($verifyRoute) }}" class="flex flex-col gap-5">
                @csrf

                <x-field name="code" label="کد تأیید" inputmode="numeric" autocomplete="one-time-code"
                         required numeric autofocus :error="$errors->first('code')" />

                <x-button type="submit" variant="primary" size="lg" block>ورود به حساب</x-button>
            </form>

            {{--
                شمارش معکوس فقط برای اینکه کاربر روی دکمه‌ای نزند که سرور ردش می‌کند؛
                محدودیت واقعی سمت سرور است و این‌جا هیچ تصمیمی گرفته نمی‌شود.
            --}}
            <div class="mt-5 flex items-center justify-between border-t border-line pt-5"
                 data-countdown="{{ $resendAfter }}">
                <form method="POST" action="{{ route($resendRoute) }}">
                    @csrf
                    <x-button type="submit" variant="ghost" size="sm" data-countdown-button>
                        <span data-countdown-ready>ارسال دوباره کد</span>
                        <span data-countdown-waiting hidden>
                            ارسال دوباره تا <span data-countdown-seconds>@fa($resendAfter)</span> ثانیه دیگر
                        </span>
                    </x-button>
                </form>

                <a href="{{ route($changeRoute) }}" class="inline-flex h-touch items-center text-label font-semibold">{{ $changeLabel }}</a>
            </div>

            @if ($hint)
                <p class="mt-4 text-note text-muted">{{ $hint }}</p>
            @endif

            <p class="mt-4 text-note text-muted">
                پس از @fa(config('identity.otp.max_attempts')) تلاش نادرست، این کد می‌سوزد و باید کد تازه بگیرید.
            </p>
        </x-card>
    </main>
</x-layouts.base>
