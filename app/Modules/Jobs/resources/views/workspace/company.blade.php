@php use App\Modules\Jobs\Domain\Enums\ReviewStatus; @endphp

<x-layouts.workspace art="company-edit" title="صفحه شرکت" heading="صفحه شرکت"
                     lede="صفحه‌ای که کارجوها کنار آگهی‌های شما می‌بینند. هر ویرایش پیش از دیده‌شدن از تأیید مدیر می‌گذرد و آگهی فقط زیر شرکت تأییدشده ثبت می‌شود."
                     nav="company" help="company">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($errors->has('company') || $errors->has('document'))
        <div class="mb-6"><x-alert tone="error">{{ $errors->first('company') ?: $errors->first('document') }}</x-alert></div>
    @endif

    @if ($company)
        <div class="mb-6">
            @switch($company->status)
                @case(ReviewStatus::Pending)
                    <x-alert tone="info">
                        ویرایش شما در انتظار تأیید مدیر است.
                        @if ($company->isListed()) تا تأیید، نسخه قبلی روی سایت می‌ماند. @endif
                    </x-alert>
                    @break
                @case(ReviewStatus::Rejected)
                    <x-alert tone="caution" title="ویرایش برای اصلاح برگشت">{{ $company->review_note }}</x-alert>
                    @break
                @default
                    @if ($company->isListed())
                        <x-alert tone="success">صفحه شرکت منتشر شده است؛ حالا می‌توانید آگهی ثبت کنید.</x-alert>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <x-button :href="route('jobs.employer.postings.create')" variant="primary" size="sm" icon="plus">آگهی تازه</x-button>
                            <x-button :href="route('jobs.companies.show', $company->slug)" variant="secondary" size="sm">دیدن صفحه عمومی</x-button>
                        </div>
                    @endif
            @endswitch
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-card class="min-w-0">
            <form method="POST" action="{{ route('jobs.company.update') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
                @csrf

                <x-field name="name" label="نام شرکت" :value="old('name', $draft?->name)" required
                         hint="نام ثبتی یا نام تجاری شناخته‌شده؛ مثلاً «فولاد سپاهان»." :error="$errors->first('name')" />

                @if ($company?->published_at === null)
                    <x-field name="slug" label="نشانی صفحه" :value="old('slug', $draft?->slug)" required dir="ltr"
                             hint="حروف کوچک لاتین، رقم و خط تیره؛ پس از نخستین انتشار عوض نمی‌شود." :error="$errors->first('slug')" />
                @else
                    <div>
                        <p class="mb-2 text-label font-semibold text-ink">نشانی صفحه</p>
                        <p class="text-copy text-body"><span data-numeric dir="ltr">/companies/{{ $company->slug }}</span></p>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="industry" label="صنعت" :value="old('industry', $draft?->industry)" required
                             hint="مثلاً فولاد، پتروشیمی، ساختمان یا خدمات HSE." :error="$errors->first('industry')" />
                    <div>
                        <label for="size" class="mb-2 block text-label font-semibold text-ink">اندازه</label>
                        <select id="size" name="size" required class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                            <option value="">انتخاب کنید</option>
                            @foreach ($sizes as $size)
                                <option value="{{ $size->value }}" @selected(old('size', $draft?->size) === $size->value)>{{ $size->label() }}</option>
                            @endforeach
                        </select>
                        @error('size')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @include('jobs::workspace._place', ['province' => old('province', $draft?->province), 'city' => old('city', $draft?->city)])

                <div>
                    <label for="about" class="mb-2 block text-label font-semibold text-ink">معرفی <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea id="about" name="about" rows="7" required aria-describedby="about-hint"
                              @if ($errors->has('about')) aria-invalid="true" @endif
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('about', $draft?->about) }}</textarea>
                    <p id="about-hint" class="mt-1 text-note text-muted">
                        شرکت چه می‌کند و واحد HSE چه جایگاهی دارد. شماره تماس و ایمیل ننویسید؛ کارجوها از راه سایت درخواست می‌فرستند.
                    </p>
                    @error('about')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="logo" class="mb-2 block text-label font-semibold text-ink">نشان شرکت</label>
                    <div class="flex flex-wrap items-center gap-4">
                        @if ($logo)
                            <img src="{{ $logo->url }}" alt="نشان فعلی" width="64" height="64" class="size-16 rounded-md object-cover">
                        @endif
                        <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" aria-describedby="logo-hint"
                               class="block min-h-touch text-label text-muted">
                    </div>
                    <p id="logo-hint" class="mt-1 text-note text-muted">اختیاری؛ روی خود سایت ذخیره و اطلاعات مکان و دستگاه از آن پاک می‌شود.</p>
                    @error('logo')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-button type="submit" variant="primary" icon="check">فرستادن برای تأیید</x-button>
                </div>
            </form>
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card title="مدرک خصوصی شرکت" heading="text-h4">
                <p class="text-copy text-body">
                    آگهی روزنامه رسمی، جواز یا معرفی‌نامه شرکت را برای سنجش مدیر بفرستید. فقط شما و مدیر آن را می‌بینید و روی صفحه عمومی نمی‌آید.
                </p>

                @if ($documents->isNotEmpty())
                    <ul class="mt-4 flex list-none flex-col gap-2 ps-0">
                        @foreach ($documents as $document)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ route('jobs.documents.download', $document->uuid) }}" class="min-w-0 truncate text-label">{{ $document->original_name }}</a>
                                <form method="POST" action="{{ route('jobs.documents.destroy', $document->uuid) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" aria-label="برداشتن {{ $document->original_name }}">برداشتن</x-button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($documents->count() < (int) ($documentRules['max'] ?? 3))
                    <form method="POST" action="{{ route('jobs.documents.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3">
                        @csrf
                        <label for="document" class="text-label font-semibold text-ink">فایل تازه</label>
                        <input id="document" type="file" name="document" required accept=".pdf,image/jpeg,image/png,image/webp"
                               class="block min-h-touch text-label text-muted">
                        <div>
                            <x-button type="submit" variant="secondary" icon="upload">فرستادن مدرک</x-button>
                        </div>
                    </form>
                @endif
            </x-card>

            <x-card title="پیش از فرستادن" heading="text-h4">
                <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                    <li>نشان «کارفرمای تأییدشده در فرابهداشت» یعنی مدیر مدرک را دیده؛ مجوز رسمی نیست.</li>
                    <li>شماره و ایمیل شرکت روی سایت نمی‌آید.</li>
                    <li>کارجو هیچ‌وقت برای درخواست پول نمی‌دهد؛ آگهی‌ای که از کارجو پول بخواهد برمی‌گردد.</li>
                </ul>
            </x-card>
        </div>
    </div>

</x-layouts.workspace>
