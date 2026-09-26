<x-layouts.workspace art="expert-create" title="پرسش تازه از متخصص"
                     heading="پرسش تازه از متخصص"
                     lede="پرسش شما پس از تأیید مدیر به دست مشاوران تأییدشده می‌رسد. پرسیدن رایگان است."
                     nav="my-questions" help="expert-ask">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['پرسش‌های من', route('expert.mine')], ['پرسش تازه', null]]" />
    </x-slot:breadcrumb>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-card class="min-w-0">
            <form method="POST" action="{{ route('expert.store') }}" class="flex flex-col gap-5">
                @csrf

                <x-field name="title" label="عنوان پرسش" :value="old('title')" required
                         hint="یک جمله که پرسش را روشن بگوید؛ مثلاً «فاصله ایمن از کمپرسور ۹۵ دسی‌بلی برای کار ۸ ساعته چقدر است؟»"
                         :error="$errors->first('title')" />

                <div>
                    <label for="topic" class="mb-2 block text-label font-semibold text-ink">حوزه</label>
                    <select id="topic" name="topic" required
                            class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->value }}" @selected(old('topic') === $topic->value)>{{ $topic->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="body" class="mb-2 block text-label font-semibold text-ink">شرح پرسش</label>
                    <textarea id="body" name="body" rows="10" required aria-describedby="body-hint"
                              @if ($errors->has('body')) aria-invalid="true" @endif
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('body') }}</textarea>
                    <p id="body-hint" class="mt-1 text-note text-muted">
                        شرایط کار، عددهایی که اندازه گرفته‌اید و آنچه تا حالا امتحان کرده‌اید. نام شرکت، نام افراد و شماره تماس ننویسید.
                    </p>
                    @error('body')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="mb-2 text-label font-semibold text-ink">چه کسی پرسش را ببیند؟</legend>
                    @foreach ($visibilities as $visibility)
                        <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                            <input type="radio" name="visibility" value="{{ $visibility->value }}"
                                   @checked(old('visibility', 'public') === $visibility->value)
                                   class="size-5 shrink-0 accent-primary">
                            {{ $visibility->label() }}
                        </label>
                    @endforeach
                    <p class="mt-1 text-note text-muted">
                        پرسش عمومی به دیگران هم کمک می‌کند. در هر دو حالت نام شما هیچ‌جا نمایش داده نمی‌شود.
                    </p>
                </fieldset>

                <div>
                    <x-button type="submit" variant="primary" icon="check">فرستادن پرسش</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="پیش از فرستادن" heading="text-h4">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                <li>پاسخ‌ها نظر کارشناسی‌اند؛ تشخیص پزشکی یا تأیید انطباق قانونی نیستند.</li>
                <li>برای محاسبه‌های رایج، اول ابزارهای فرابهداشت را ببینید؛ شاید جواب همان‌جا باشد.</li>
                <li>مشترک حرفه‌ای در صف پاسخ‌دهنده‌ها و صف تأیید مدیر جلوتر است.</li>
            </ul>
        </x-card>
    </div>

</x-layouts.workspace>
