<x-layouts.workspace art="consulting-order-create" title="درخواست خدمت"
                     :heading="'درخواست: '.$service->title"
                     :lede="$profile->display_name.' · '.$service->kind->label().' · '.$service->price()->format()"
                     nav="consulting-orders" help="consulting-order">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['مشاوران', route('consulting.index')], [$profile->display_name, route('consulting.show', $profile->slug)], ['درخواست خدمت', null]]" />
    </x-slot:breadcrumb>

    @if ($errors->has('order'))
        <div class="mb-6"><x-alert tone="error">{{ $errors->first('order') }}</x-alert></div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-card class="min-w-0">
            @if (! $open)
                <x-alert tone="info">خرید خدمت مشاوره فعلاً بسته است. کمی بعد دوباره سر بزنید.</x-alert>
            @else
                <form method="POST" action="{{ route('consulting.orders.store', $service->uuid) }}" class="flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="need" class="mb-2 block text-label font-semibold text-ink">چه نیازی دارید؟ <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea id="need" name="need" rows="7" required aria-describedby="need-hint"
                                  @if ($errors->has('need')) aria-invalid="true" @endif
                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('need') }}</textarea>
                        <p id="need-hint" class="mt-1 text-note text-muted">نوع کار، اندازه کارگاه، عددهایی که دارید و آنچه از جلسه می‌خواهید. فقط مشاور همین درخواست آن را می‌بیند.</p>
                        @error('need')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-label font-semibold text-ink">دو یا سه زمان پیشنهادی</legend>
                        <div class="flex flex-col gap-3">
                            @foreach ([0, 1, 2] as $i)
                                <div>
                                    <label for="time-{{ $i }}" class="sr-only">زمان پیشنهادی {{ $i + 1 }}</label>
                                    <input id="time-{{ $i }}" type="text" name="times[]" value="{{ old('times.'.$i) }}" @if ($i < 2) required @endif
                                           placeholder="{{ ['مثلاً شنبه ۱۲ مهر، ساعت ۱۰ تا ۱۲', 'مثلاً دوشنبه ۱۴ مهر، عصر', 'اختیاری'][$i] }}"
                                           class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                </div>
                            @endforeach
                        </div>
                        @error('times')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    @if ($cities !== [])
                        <div>
                            <label for="city" class="mb-2 block text-label font-semibold text-ink">شهر بازدید</label>
                            <select id="city" name="city" required
                                    class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                @foreach ($cities as [$key, $name])
                                    <option value="{{ $key }}" @selected(old('city') === $key)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                        <input type="checkbox" name="share_mobile" value="1" @checked(old('share_mobile')) class="size-5 shrink-0 accent-primary">
                        شماره موبایلم را به مشاور بده
                    </label>
                    <p class="-mt-3 text-note text-muted">اگر تیک نزنید، شماره شما به مشاور نشان داده نمی‌شود و گفت‌وگو در صفحه همین درخواست است.</p>

                    <x-payment-method :total="$service->price()" />

                    <div>
                        <x-button type="submit" variant="primary">پرداخت و فرستادن درخواست</x-button>
                    </div>
                </form>
            @endif
        </x-card>

        <x-card title="پول شما کجا می‌ماند؟" heading="text-h4">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                <li>مبلغ تا پایان کار نزد فرابهداشت امانت است، نه نزد مشاور.</li>
                <li>اگر مشاور نپذیرد یا تا @fa($limits['reply_hours'] ?? 48) ساعت پاسخ ندهد، کامل به کیف پول شما برمی‌گردد.</li>
                <li>پس از «انجام شد»، تأیید می‌کنید یا اعتراض ثبت می‌کنید؛ اعتراض را مدیر بررسی می‌کند.</li>
                <li>نظر مشاور کارشناسی است؛ تشخیص پزشکی یا تأیید انطباق قانونی نیست.</li>
            </ul>
        </x-card>
    </div>

</x-layouts.workspace>
