<x-layouts.workspace art="identity-account" title="حساب من"
                     heading="حساب من"
                     lede="نامی که در میزکار، گزارش‌ها و گواهی دوره‌ها می‌آید، و راه‌های تماس با شما."
                     nav="account" help="account">

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <x-card title="اطلاعات شما">

            <form method="POST" action="{{ route('identity.account.update') }}" class="mt-5 flex flex-col gap-5">
                @csrf
                @method('PUT')

                <x-field name="name" label="نام و نام خانوادگی" required autocomplete="name"
                         :value="old('name', $user->name)" :error="$errors->first('name')"
                         hint="در گزارش‌های PDF و گواهی پایان دوره همین نام چاپ می‌شود." />

                <x-field name="email" type="email" label="ایمیل (اختیاری)" autocomplete="email" dir="ltr"
                         :value="old('email', $user->email)" :error="$errors->first('email')"
                         hint="فقط برای رسید و اطلاع‌رسانی؛ ورود همچنان با موبایل است." />

                <div>
                    <x-button type="submit" variant="primary">ذخیره</x-button>
                </div>
            </form>
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card title="شماره ورود">
                <p class="mt-3 text-copy text-ink">
                    <span dir="ltr" data-numeric>{{ $mobile ?? $user->mobile }}</span>
                </p>
                <p class="mt-2 text-note text-muted">
                    کد ورود به همین شماره پیامک می‌شود. برای تغییر آن با پشتیبانی تماس بگیرید.
                </p>
            </x-card>

            <x-card title="دستگاه‌های دیگر">
                <p class="mt-3 text-copy text-muted">
                    @if ($otherSessions === null)
                        اگر گوشی یا رایانه‌ای را گم کرده‌اید یا روی دستگاه مشترک وارد شده بودید، از همه دستگاه‌های دیگر خارج شوید.
                    @elseif ($otherSessions === 0)
                        اکنون فقط روی همین دستگاه وارد هستید.
                    @else
                        روی @fa($otherSessions) دستگاه دیگر هم وارد هستید.
                    @endif
                </p>

                <form method="POST" action="{{ route('identity.account.sign-out-others') }}" class="mt-5">
                    @csrf
                    <x-button type="submit" variant="secondary">خروج از همه دستگاه‌های دیگر</x-button>
                </form>
            </x-card>
        </div>
    </div>

</x-layouts.workspace>
