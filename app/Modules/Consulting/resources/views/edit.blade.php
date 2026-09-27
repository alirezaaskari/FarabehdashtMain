@php use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus; @endphp

<x-layouts.workspace art="consultant-profile-edit" title="صفحه عمومی مشاور"
                     heading="صفحه عمومی مشاور"
                     lede="صفحه‌ای که کارفرما و کارشناس‌ها در فهرست مشاوران می‌بینند. هر ویرایش پیش از دیده‌شدن از تأیید مدیر می‌گذرد."
                     nav="consultant-profile" help="consultant-profile">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($errors->has('profile') || $errors->has('document'))
        <div class="mb-6"><x-alert tone="error">{{ $errors->first('profile') ?: $errors->first('document') }}</x-alert></div>
    @endif

    @if ($profile)
        <div class="mb-6">
            @switch($profile->status)
                @case(ProfileReviewStatus::Pending)
                    <x-alert tone="info">
                        ویرایش شما در انتظار تأیید مدیر است.
                        @if ($profile->isListed()) تا تأیید، نسخه قبلی روی سایت می‌ماند. @endif
                    </x-alert>
                    @break
                @case(ProfileReviewStatus::Rejected)
                    <x-alert tone="caution" title="ویرایش برای اصلاح برگشت">{{ $profile->review_note }}</x-alert>
                    @break
                @default
                    @if ($profile->isListed())
                        <x-alert tone="success">صفحه شما منتشر شده است و در فهرست مشاوران دیده می‌شود.</x-alert>
                        <div class="mt-3">
                            <x-button :href="route('consulting.show', $profile->slug)" variant="secondary" size="sm">دیدن صفحه عمومی</x-button>
                        </div>
                    @endif
            @endswitch
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-card class="min-w-0">
            <form method="POST" action="{{ route('consulting.profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
                @csrf

                <x-field name="display_name" label="نام نمایشی" :value="old('display_name', $draft?->displayName)" required
                         hint="همان نامی که همکاران شما را با آن می‌شناسند؛ مثلاً «مهندس سارا احمدی»."
                         :error="$errors->first('display_name')" />

                @if ($profile?->published_at === null)
                    <x-field name="slug" label="نشانی صفحه" :value="old('slug', $draft?->slug)" required dir="ltr"
                             hint="حروف کوچک لاتین، رقم و خط تیره؛ پس از نخستین انتشار عوض نمی‌شود."
                             :error="$errors->first('slug')" />
                @else
                    <div>
                        <p class="mb-2 text-label font-semibold text-ink">نشانی صفحه</p>
                        <p class="text-copy text-body"><span data-numeric dir="ltr">/consultants/{{ $profile->slug }}</span></p>
                    </div>
                @endif

                <x-field name="headline" label="عنوان کوتاه" :value="old('headline', $draft?->headline)" required
                         hint="یک خط درباره کارتان؛ مثلاً «کارشناس ارشد بهداشت حرفه‌ای، اندازه‌گیری عوامل زیان‌آور»."
                         :error="$errors->first('headline')" />

                <div>
                    <label for="bio" class="mb-2 block text-label font-semibold text-ink">معرفی <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea id="bio" name="bio" rows="8" required aria-describedby="bio-hint"
                              @if ($errors->has('bio')) aria-invalid="true" @endif
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('bio', $draft?->bio) }}</textarea>
                    <p id="bio-hint" class="mt-1 text-note text-muted">
                        چه کارهایی انجام می‌دهید و برای چه صنایعی. شماره تماس، ایمیل و ادعای «مدرک رسمی» یا «تأیید ایمنی» ننویسید.
                    </p>
                    @error('bio')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="province" class="mb-2 block text-label font-semibold text-ink">استان</label>
                        <select id="province" name="province" required
                                class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                            <option value="">انتخاب کنید</option>
                            @foreach ($regions as $key => $region)
                                <option value="{{ $key }}" @selected(old('province', $draft?->province) === $key)>{{ $region['name'] }}</option>
                            @endforeach
                        </select>
                        @error('province')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="city" class="mb-2 block text-label font-semibold text-ink">شهر</label>
                        <select id="city" name="city" required
                                class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                            <option value="">انتخاب کنید</option>
                            @foreach ($regions as $region)
                                <optgroup label="{{ $region['name'] }}">
                                    @foreach ($region['cities'] as $key => $name)
                                        <option value="{{ $key }}" @selected(old('city', $draft?->city) === $key)>{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('city')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($terms !== [])
                    <fieldset>
                        <legend class="mb-2 text-label font-semibold text-ink">حوزه‌های تخصص</legend>
                        <div class="grid gap-x-4 sm:grid-cols-2">
                            @foreach ($terms as $term)
                                <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                    <input type="checkbox" name="domains[]" value="{{ $term->id }}"
                                           @checked(in_array($term->id, array_map(intval(...), (array) old('domains', $draft?->domainIds ?? [])), true))
                                           class="size-5 shrink-0 accent-primary">
                                    {{ $term->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('domains')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endif

                <div>
                    <label for="experience" class="mb-2 block text-label font-semibold text-ink">سابقه کار</label>
                    <textarea id="experience" name="experience" rows="4" aria-describedby="experience-hint"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('experience', $draft?->experience) }}</textarea>
                    <p id="experience-hint" class="mt-1 text-note text-muted">روی صفحه با برچسب «به اظهار مشاور» می‌آید.</p>
                </div>

                <div>
                    <label for="education" class="mb-2 block text-label font-semibold text-ink">تحصیلات</label>
                    <textarea id="education" name="education" rows="3" aria-describedby="education-hint"
                              class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('education', $draft?->education) }}</textarea>
                    <p id="education-hint" class="mt-1 text-note text-muted">روی صفحه با برچسب «به اظهار مشاور» می‌آید.</p>
                </div>

                <div>
                    <label for="photo" class="mb-2 block text-label font-semibold text-ink">عکس</label>
                    <div class="flex flex-wrap items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo->url }}" alt="عکس فعلی" width="64" height="64" class="size-16 rounded-full object-cover">
                        @endif
                        <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-hint"
                               class="block min-h-touch text-label text-muted">
                    </div>
                    <p id="photo-hint" class="mt-1 text-note text-muted">عکس چهره روشن؛ روی خود سایت ذخیره و اطلاعات مکان و دستگاه از آن پاک می‌شود.</p>
                    @error('photo')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-button type="submit" variant="primary" icon="check">فرستادن برای تأیید</x-button>
                </div>
            </form>
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card title="مدرک‌های خصوصی" heading="text-h4">
                <p class="text-copy text-body">
                    مدرک تحصیلی یا سابقه کار را برای سنجش مدیر بفرستید. فقط شما و مدیر آن‌ها را می‌بینید و روی صفحه عمومی هیچ نشانی از آن‌ها نیست.
                </p>

                @if ($documents->isNotEmpty())
                    <ul class="mt-4 flex list-none flex-col gap-2 ps-0">
                        @foreach ($documents as $document)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ route('consulting.documents.download', $document->uuid) }}" class="min-w-0 truncate text-label">{{ $document->original_name }}</a>
                                <form method="POST" action="{{ route('consulting.documents.destroy', $document->uuid) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" aria-label="برداشتن {{ $document->original_name }}">برداشتن</x-button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($documents->count() < (int) ($documentRules['max'] ?? 5))
                    <form method="POST" action="{{ route('consulting.documents.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3">
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
                    <li>روی صفحه عمومی شماره تماس و ایمیل نمی‌آید؛ کاربران از راه سایت با شما در تماس‌اند.</li>
                    <li>هیچ‌جا «مدرک تأییدشده» نوشته نمی‌شود؛ سابقه و تحصیلات به اظهار خود شماست.</li>
                    <li>پاسخ‌هایتان در «پرسش از متخصص» خودکار روی صفحه‌تان فهرست می‌شود.</li>
                </ul>
            </x-card>
        </div>
    </div>

</x-layouts.workspace>
