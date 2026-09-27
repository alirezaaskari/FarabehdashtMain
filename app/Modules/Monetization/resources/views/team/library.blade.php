@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="team-library"
                     title="کتابخانه تیم"
                     heading="کتابخانه تیم"
                     lede="فایل‌هایی که اعضای تیم برای هم گذاشته‌اند: قالب گزارش، دستورالعمل، فرم خام."
                     nav="team"
                     help="team-library">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($team === null)
        <x-empty-state art="empty-team-library" icon="lock"
                       title="کتابخانه تیم برای اعضای تیم است"
                       description="وقتی تیم بخرید یا دعوت تیمی را بپذیرید، کتابخانه مشترک همین‌جا باز می‌شود.">
            <x-slot:action>
                <x-button :href="route('monetization.team')" variant="primary">صفحه تیم</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        @if ($team->isCurrent())
            <form method="POST" action="{{ route('monetization.team.library.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-xl border border-line bg-surface px-5 py-5">
                @csrf
                <x-field name="title" label="عنوان فایل" :value="old('title')" required :error="$errors->first('title')" hint="مثلاً «قالب گزارش صدای کارگاه»." />
                <div>
                    <label for="file" class="mb-2 block text-label font-semibold text-ink">فایل <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="file" name="file" type="file" required aria-describedby="file-hint"
                           accept="{{ implode(',', array_map(fn (string $ext): string => '.'.$ext, $mimes)) }}"
                           class="block min-h-touch w-full text-control text-body">
                    <p id="file-hint" class="mt-1 text-note text-muted">
                        تا @fa(intdiv($maxKb, 1024)) مگابایت. فقط فایل‌هایی که خودتان ساخته‌اید؛ فایل خریداری‌شده از فروشگاه مجوزش مال خریدار است و این‌جا گذاشته نمی‌شود.
                    </p>
                    @error('file')
                        <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div><x-button type="submit" variant="primary" icon="upload">بارگذاری</x-button></div>
            </form>
        @else
            <x-alert tone="caution">اشتراک تیم تمام شده است؛ فایل‌ها را برمی‌دارید ولی فایل تازه گذاشته نمی‌شود.</x-alert>
        @endif

        <div class="mt-8">
            @if ($files->isEmpty())
                <x-empty-state icon="file" title="هنوز فایلی نیست"
                               description="اولین فایل مشترک تیم را شما بگذارید؛ همه اعضا آن را می‌بینند." />
            @else
                <ul class="flex list-none flex-col gap-2 ps-0">
                    @foreach ($files as $file)
                        <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 border-b border-line py-2">
                            <a href="{{ route('monetization.team.library.download', $file->uuid) }}" class="inline-flex min-h-touch min-w-0 flex-col justify-center">
                                <span class="text-label text-ink">{{ $file->title }}</span>
                                <span class="text-note text-muted">{{ $file->uploader->getFilamentName() }} · {{ JalaliDate::short($file->created_at) }} · @fa(max(1, intdiv($file->size_bytes, 1024))) کیلوبایت</span>
                            </a>
                            @if ($file->uploader_id === $user->id || $team->owner_id === $user->id)
                                <form method="POST" action="{{ route('monetization.team.library.destroy', $file->uuid) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary">برداشتن</x-button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $files->links() }}</div>
            @endif
        </div>
    @endif

</x-layouts.workspace>
