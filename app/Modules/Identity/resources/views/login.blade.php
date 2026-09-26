<x-layouts.base title="ورود به حساب" theme="light" noindex>
    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="bg-ink p-10 lg:w-[28rem] lg:shrink-0">
            <span class="flex h-touch items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-on-primary">
                    <x-icon name="shield" :size="18" />
                </span>
                <span class="text-h3 font-bold text-surface">{{ config('app.name') }}</span>
            </span>

            {{-- تصویر روی کارت روشن می‌نشیند تا رنگ‌ها و خطش روی ستون تیره هم خوانا بماند. --}}
            <div class="mt-10 hidden w-fit rounded-xl bg-surface p-3 lg:block">
                <x-art name="login-welcome" class="h-40 w-auto" />
            </div>

            <h1 class="mt-12 text-h1 font-bold leading-relaxed text-surface lg:mt-6">یک حساب،<br>همه نقش‌ها.</h1>

            <p class="mt-4 text-label text-primary-soft/80">
                حساب شما یکی است. نقش‌های کارجو، کارفرما، فروشنده، مدرس و مشاور به‌صورت پروفایل
                روی همان حساب فعال می‌شوند. هیچ‌وقت حساب دوم نمی‌سازید.
            </p>

            <ul class="mt-8 flex flex-col gap-4">
                @foreach ([
                    'دیدن آگهی شغلی و ارسال درخواست استخدام برای کارجو همیشه رایگان است.',
                    'اطلاعات تماس شما بدون اجازه صریح خودتان نمایش داده نمی‌شود.',
                    'حضور در بانک رزومه فقط با تأیید صریح شما انجام می‌شود.',
                ] as $promise)
                    <li class="flex items-start gap-3 text-label text-primary-soft/90">
                        <span class="mt-1 text-primary"><x-icon name="check" :size="17" :stroke="2.4" /></span>
                        {{ $promise }}
                    </li>
                @endforeach
            </ul>

            <p class="mt-10 text-note text-primary-soft/60">
                کد تأیید با پیامک فرستاده می‌شود و @fa(intdiv((int) config('identity.otp.ttl_seconds'), 60)) دقیقه اعتبار دارد.
            </p>
        </aside>

        <main id="main" class="flex grow items-center justify-center p-6 lg:p-14">
            <x-card class="w-full max-w-lg" title="ورود یا ثبت‌نام"
                    subtitle="شماره موبایل خود را وارد کنید. اگر حساب نداشته باشید، همین‌جا ساخته می‌شود.">
                <form method="POST" action="{{ route('identity.login.store') }}" class="flex flex-col gap-5">
                    @csrf

                    <x-field name="mobile" label="شماره موبایل" type="tel" inputmode="numeric"
                             autocomplete="tel" numeric :value="old('mobile')" required
                             :error="$errors->first('mobile')"
                             hint="فقط برای ورود و اطلاع‌رسانی استفاده می‌شود." />

                    <div>
                        <label for="terms" class="flex min-h-touch cursor-pointer items-start gap-3 text-note text-muted">
                            <input id="terms" name="terms" type="checkbox" value="1" @checked(old('terms'))
                                   @if ($errors->has('terms')) aria-invalid="true" aria-describedby="terms-error" @endif
                                   class="mt-0.5 size-5 shrink-0 accent-primary">
                            @if (Route::has('workspace.legal.show'))
                                <span>
                                    <a href="{{ route('workspace.legal.show', 'terms') }}" target="_blank" class="inline-flex min-h-touch items-center">قوانین استفاده</a>
                                    و
                                    <a href="{{ route('workspace.legal.show', 'privacy') }}" target="_blank" class="inline-flex min-h-touch items-center">سیاست حریم خصوصی</a>
                                    فرابهداشت را می‌پذیرم.
                                </span>
                            @else
                                قوانین استفاده و سیاست حریم خصوصی فرابهداشت را می‌پذیرم.
                            @endif
                        </label>

                        @error('terms')
                            <p id="terms-error" class="mt-1 flex items-center gap-1.5 text-note font-semibold text-danger">
                                <x-icon name="alert" :size="14" :stroke="2.4" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-button type="submit" variant="primary" size="lg" block>دریافت کد تأیید</x-button>
                </form>

                @if (Route::has('identity.email.show'))
                    <div class="mt-5 border-t border-line pt-5">
                        <a href="{{ route('identity.email.show') }}" class="inline-flex h-touch items-center text-label font-semibold">ورود مدیر با ایمیل</a>
                    </div>
                @endif
            </x-card>
        </main>
    </div>
</x-layouts.base>
