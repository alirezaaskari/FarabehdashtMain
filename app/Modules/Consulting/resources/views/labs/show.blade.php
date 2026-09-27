<x-layouts.public :seo="$seo" active="directory">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['خدمات تخصصی', route('consulting.directory.index')], [$profile->display_name, null]]" />
    </x-slot:breadcrumb>

    <article>
        <header class="flex flex-wrap items-center gap-5">
            @if ($photo)
                <img src="{{ $photo->url }}" alt="نشان {{ $profile->display_name }}" width="112" height="112"
                     class="size-28 shrink-0 rounded-full object-cover">
            @else
                <span aria-hidden="true" class="flex size-28 shrink-0 items-center justify-center rounded-full bg-primary-soft text-display text-on-primary-soft">{{ mb_substr((string) $profile->display_name, 0, 1) }}</span>
            @endif
            <div class="min-w-0">
                <h1 class="text-h1 text-ink">{{ $profile->display_name }}</h1>
                <p class="mt-1 text-lede text-body">{{ $profile->headline }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-badge tone="primary" icon="shield">آزمایشگاه تأییدشده در فرابهداشت</x-badge>
                    @if ($place !== '')
                        <x-badge icon="compass">{{ $place }}</x-badge>
                    @endif
                </div>
            </div>
        </header>

        <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <section aria-labelledby="bio-heading">
                    <h2 id="bio-heading" class="text-h2 text-ink">معرفی</h2>
                    @foreach ($profile->bioParagraphs() as $paragraph)
                        <p class="mt-4 text-copy text-body">{{ $paragraph }}</p>
                    @endforeach
                </section>

                <section aria-labelledby="offerings-heading" class="mt-10">
                    <h2 id="offerings-heading" class="text-h3 text-ink">خدمت‌ها</h2>
                    <p class="mt-1 text-note text-muted">به اظهار آزمایشگاه</p>
                    <ul class="mt-4 flex list-none flex-wrap gap-2 ps-0">
                        @foreach ($offerings as $key => $name)
                            <li>
                                <a href="{{ $profile->city ? route('consulting.directory.city', [$key, $profile->city]) : route('consulting.directory.service', $key) }}"
                                   class="inline-flex min-h-touch items-center rounded-full border border-line bg-surface px-4 text-label text-ink no-underline hover:bg-surface-2 hover:no-underline">{{ $name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($profile->experience)
                    <section aria-labelledby="experience-heading" class="mt-10">
                        <h2 id="experience-heading" class="text-h3 text-ink">سابقه کار</h2>
                        <p class="mt-1 text-note text-muted">به اظهار آزمایشگاه</p>
                        <p class="mt-3 whitespace-pre-line text-copy text-body">{{ $profile->experience }}</p>
                    </section>
                @endif
            </div>

            <aside>
                <x-card title="درخواست تماس" heading="text-h4">
                    <p class="text-copy text-body">
                        شماره و نشانی آزمایشگاه روی سایت نمی‌آید. نیازتان را بنویسید؛ پاسخ آزمایشگاه در میزکار شما می‌آید و اعلان می‌گیرید.
                    </p>
                    @auth
                        <form method="POST" action="{{ route('consulting.contacts.store', $profile->slug) }}" class="mt-4 flex flex-col gap-4">
                            @csrf
                            @error('contact')
                                <x-alert tone="error">{{ $message }}</x-alert>
                            @enderror
                            <div>
                                <label for="service" class="mb-2 block text-label font-semibold text-ink">خدمت</label>
                                <select id="service" name="service" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                                    <option value="">انتخاب نشده</option>
                                    @foreach ($offerings as $key => $name)
                                        <option value="{{ $key }}" @selected(old('service') === $key)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="message" class="mb-2 block text-label font-semibold text-ink">نیاز شما <span class="text-danger" aria-hidden="true">*</span></label>
                                <textarea id="message" name="message" rows="5" required aria-describedby="message-hint"
                                          @if ($errors->has('message')) aria-invalid="true" @endif
                                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('message') }}</textarea>
                                <p id="message-hint" class="mt-1 text-note text-muted">نوع کارگاه، تعداد ایستگاه یا نمونه و زمان مورد نظر. کمینه @fa($limits['contact_min'] ?? 20) نویسه.</p>
                                @error('message')
                                    <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="checkbox" name="share_mobile" value="1" @checked(old('share_mobile')) class="size-5 shrink-0 accent-primary">
                                شماره موبایلم را به این آزمایشگاه نشان بده
                            </label>
                            <div><x-button type="submit" variant="primary">فرستادن درخواست</x-button></div>
                        </form>
                    @else
                        @if (Route::has('login'))
                            <div class="mt-4">
                                <x-button :href="route('login')" variant="primary">ورود برای درخواست تماس</x-button>
                            </div>
                        @endif
                    @endauth
                </x-card>
            </aside>
        </div>

        <x-disclaimer class="mt-10">
            متن این صفحه را خود آزمایشگاه نوشته و مدیر فرابهداشت پیش از انتشار بازبینی‌اش کرده است. خدمت‌ها و سابقه به اظهار آزمایشگاه است؛ این صفحه گواهی، مجوز یا تأیید رسمی آزمایشگاه نیست.
        </x-disclaimer>
    </article>

</x-layouts.public>
