<x-layouts.workspace art="apply-form" title="ارسال درخواست"
                     :heading="'درخواست برای '.$posting->title"
                     :lede="$posting->company->name.' · '.$catalog->cityName($posting->city).'. ارسال درخواست همیشه رایگان است.'"
                     nav="applications" help="apply-form">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['کاریابی', route('jobs.index')], [$posting->title, route('jobs.show', $posting->id)], ['ارسال درخواست', null]]" />
    </x-slot:breadcrumb>

    @error('application')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-card class="min-w-0">
            @if ($ownPosting)
                <x-alert tone="info">این آگهی شرکت خودتان است و برایش درخواست فرستاده نمی‌شود.</x-alert>
            @elseif (! $canApply)
                <x-permission-notice title="اول نقش کارجو را فعال کنید"
                                     description="نقش کارجو تأیید مدیر نمی‌خواهد و همان لحظه فعال می‌شود. بعد به همین صفحه برگردید.">
                    @if (Route::has('identity.profiles'))
                        <x-slot:action>
                            <x-button :href="route('identity.profiles')" variant="primary">فعال‌کردن نقش کارجو</x-button>
                        </x-slot:action>
                    @endif
                </x-permission-notice>
            @else
                <form method="POST" action="{{ route('jobs.apply.store', $posting->id) }}" enctype="multipart/form-data" class="flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="cover_note" class="mb-2 block text-label font-semibold text-ink">چند خط معرفی <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea id="cover_note" name="cover_note" rows="6" required minlength="{{ $limits['cover_min'] ?? 30 }}" maxlength="{{ $limits['cover_max'] ?? 2000 }}"
                                  aria-describedby="cover_note-hint"
                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('cover_note') }}</textarea>
                        <p id="cover_note-hint" class="mt-1 text-note text-muted">چرا برای همین شغل مناسبید: سابقه مرتبط، ابزارهایی که با آن‌ها کار کرده‌اید، شهر و زمان شروع.</p>
                        @error('cover_note')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="resume" class="mb-2 block text-label font-semibold text-ink">فایل رزومه (PDF) <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="resume" type="file" name="resume" required accept=".pdf,application/pdf" aria-describedby="resume-hint"
                               class="block min-h-touch text-label text-muted">
                        <p id="resume-hint" class="mt-1 text-note text-muted">حداکثر @fa(intdiv((int) ($limits['resume_max_kb'] ?? 5120), 1024)) مگابایت. فقط کارفرمای همین آگهی آن را می‌بیند و هر بار دیدنش برای شما ثبت می‌شود.</p>
                        @error('resume')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-toggle name="share_contact" :checked="(bool) old('share_contact')"
                              label="شماره موبایل و ایمیلم را به این کارفرما نشان بده"
                              description="خاموش بماند، کارفرما فقط از گفت‌وگوی همین درخواست با شما حرف می‌زند. بعداً هم می‌توانید روشن یا خاموشش کنید." />

                    <div><x-button type="submit" variant="primary" icon="upload">فرستادن درخواست</x-button></div>
                </form>
            @endif
        </x-card>

        <x-card title="قاعده‌ها" heading="text-h4">
            <ul class="flex list-disc flex-col gap-2 ps-5 text-copy text-body">
                <li>ارسال درخواست رایگان است و هیچ‌وقت پول نمی‌خواهد.</li>
                <li>هر روز تا @fa($perDay) درخواست می‌شود فرستاد.</li>
                <li>کارفرما درخواست‌های دیگر شما را نمی‌بیند.</li>
                <li>تا پیش از تصمیم کارفرما می‌توانید درخواست را پس بگیرید؛ رزومه همان لحظه پاک می‌شود.</li>
            </ul>
        </x-card>
    </div>

</x-layouts.workspace>
