@php
    use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
    use App\Support\JalaliDate;
@endphp

<x-layouts.workspace art="application-show" title="درخواست شغلی" :heading="$posting->title"
                     :lede="$posting->company->name.' · فرستاده‌شده '.JalaliDate::long($application->created_at)"
                     nav="applications" help="application-show">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['درخواست‌های شغلی من', route('jobs.applications.index')], [$posting->title, null]]" />
    </x-slot:breadcrumb>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('application')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="flex min-w-0 flex-col gap-6">
            <x-card title="متن درخواست" heading="text-h4">
                @foreach ($application->coverParagraphs() as $paragraph)
                    <p class="mb-3 whitespace-pre-line text-copy text-body last:mb-0">{{ $paragraph }}</p>
                @endforeach
                <p class="mt-4 text-note text-muted">
                    @if ($application->resume_name)
                        رزومه: <span data-numeric>{{ $application->resume_name }}</span>
                    @else
                        فایل رزومه پاک شده است.
                    @endif
                </p>
            </x-card>

            @include('jobs::applications._messages', ['action' => route('jobs.applications.message', $application->uuid)])
        </div>

        <div class="flex flex-col gap-6">
            <x-card title="وضعیت" heading="text-h4">
                <x-badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-badge>
                @if ($application->status_changed_at)
                    <p class="mt-2 text-note text-muted">آخرین تغییر {{ JalaliDate::long($application->status_changed_at) }}</p>
                @endif
                @if ($posting->published_at)
                    <x-button :href="route('jobs.show', $posting->id)" variant="secondary" size="sm" class="mt-4">دیدن آگهی</x-button>
                @endif
            </x-card>

            @if ($application->status !== ApplicationStatus::Withdrawn)
                <x-card title="اطلاعات تماس" heading="text-h4">
                    <form method="POST" action="{{ route('jobs.applications.consent', $application->uuid) }}" class="flex flex-col gap-3">
                        @csrf
                        <x-toggle name="share_contact" :checked="$application->share_contact"
                                  label="نمایش شماره و ایمیل به این کارفرما"
                                  description="فقط برای همین درخواست؛ هر بار که کارفرما آن را ببیند برای شما ثبت می‌شود." />
                        <div><x-button type="submit" variant="secondary" size="sm">ذخیره</x-button></div>
                    </form>
                </x-card>
            @endif

            <x-card title="دیده‌شدن اطلاعات شما" heading="text-h4">
                @if ($accesses->isEmpty())
                    <p class="text-copy text-muted">کارفرما هنوز شماره یا رزومه شما را باز نکرده است.</p>
                @else
                    <ul class="flex list-none flex-col gap-2 ps-0 text-label text-body">
                        @foreach ($accesses as $access)
                            <li>{{ $access->kind->label() }} · {{ JalaliDate::long($access->created_at) }}</li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @if ($application->status->isOpen())
                <form method="POST" action="{{ route('jobs.applications.withdraw', $application->uuid) }}">
                    @csrf
                    <x-button type="submit" variant="ghost" block>پس‌گرفتن درخواست</x-button>
                </form>
            @endif
        </div>
    </div>

</x-layouts.workspace>
