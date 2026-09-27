@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="applicant-show" title="درخواست رسیده"
                     :heading="($application->user->name ?: 'کارجوی فرابهداشت').' برای '.$posting->title"
                     :lede="'رسیده '.JalaliDate::long($application->created_at).'. شماره و رزومه را فقط وقتی لازم است باز کنید؛ کارجو هر بار دیدن را می‌بیند.'"
                     nav="employer-postings" help="applicant-show">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['آگهی‌های من', route('jobs.employer.postings.index')], [$posting->title, route('jobs.employer.applicants.index', $posting->uuid)], ['درخواست', null]]" />
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
                @if ($application->resume_path)
                    <x-button :href="route('jobs.employer.applicants.resume', $application->uuid)" variant="secondary" size="sm" class="mt-4">دانلود رزومه</x-button>
                @endif
            </x-card>

            @include('jobs::applications._messages', ['action' => route('jobs.employer.applicants.message', $application->uuid)])
        </div>

        <div class="flex flex-col gap-6">
            <x-card title="وضعیت" heading="text-h4">
                <x-badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-badge>
                <form method="POST" action="{{ route('jobs.employer.applicants.status', $application->uuid) }}" class="mt-4 flex flex-col gap-2">
                    @csrf
                    @foreach ($choices as $choice)
                        <x-button type="submit" name="status" :value="$choice->value" :variant="$application->status === $choice ? 'primary' : 'secondary'" size="sm" block
                                  :disabled="$application->status === $choice">{{ $choice->label() }}</x-button>
                    @endforeach
                </form>
                <p class="mt-3 text-note text-muted">هر تغییر وضعیت با اعلان به کارجو خبر داده می‌شود.</p>
            </x-card>

            <x-card title="اطلاعات تماس" heading="text-h4">
                @if ($contact)
                    <dl class="flex flex-col gap-2 text-label">
                        <div><dt class="text-note text-muted">موبایل</dt><dd class="text-ink" data-numeric>{{ $contact->mobile }}</dd></div>
                        @if ($contact->email)
                            <div><dt class="text-note text-muted">ایمیل</dt><dd class="text-ink" data-numeric>{{ $contact->email }}</dd></div>
                        @endif
                    </dl>
                @elseif ($application->share_contact)
                    <p class="text-copy text-body">کارجو اجازه نمایش شماره و ایمیلش را داده است.</p>
                    <form method="POST" action="{{ route('jobs.employer.applicants.contact', $application->uuid) }}" class="mt-3">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">نمایش شماره و ایمیل</x-button>
                    </form>
                @else
                    <p class="text-copy text-muted">کارجو اجازه نمایش شماره و ایمیلش را نداده است. از گفت‌وگوی همین درخواست پیام بدهید.</p>
                @endif
            </x-card>
        </div>
    </div>

</x-layouts.workspace>
