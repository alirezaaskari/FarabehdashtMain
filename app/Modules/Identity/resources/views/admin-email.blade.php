<x-layouts.base title="ورود مدیر با ایمیل" theme="light" noindex>
    <main id="main" class="mx-auto flex min-h-screen max-w-lg items-center justify-center p-6 lg:p-14">
        <x-card class="w-full" title="ورود مدیر با ایمیل" :level="1"
                subtitle="ایمیل و رمز عبوری را که برای حساب مدیر ثبت شده وارد کنید.">
            <form method="POST" action="{{ route('identity.email.store') }}" class="flex flex-col gap-5">
                @csrf

                <x-field name="email" label="ایمیل" type="email" autocomplete="username" dir="ltr"
                         :value="old('email')" required autofocus
                         :error="$errors->first('email')"
                         hint="این راه فقط برای مدیران است. بقیه کاربران با شماره موبایل وارد می‌شوند." />

                <x-field name="password" label="رمز عبور" type="password" autocomplete="current-password" dir="ltr"
                         required :error="$errors->first('password')" />

                <x-button type="submit" variant="primary" size="lg" block>ورود</x-button>
            </form>

            <div class="mt-5 border-t border-line pt-5">
                <a href="{{ route('login') }}" class="inline-flex h-touch items-center text-label font-semibold">ورود با شماره موبایل</a>
            </div>
        </x-card>
    </main>
</x-layouts.base>
