<x-layouts.workspace art="market-invite" :title="'دعوت '.$provider['name'].' به پروژه'" :heading="'دعوت '.$provider['name'].' به پروژه'"
                     lede="یکی از پروژه‌های باز خودتان را انتخاب کنید. دعوت‌شده اعلان می‌گیرد و اگر بخواهد پیشنهاد می‌دهد؛ پروژه خصوصی فقط به دعوت‌شده‌ها نشان داده می‌شود."
                     nav="market-projects" help="market-invite">

    @error('invite')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @if ($projects->isEmpty())
        <x-empty-state icon="briefcase" title="پروژه بازی ندارید"
                       description="دعوت روی پروژه‌ای می‌نشیند که تأیید شده و مهلت پیشنهادش نگذشته است. اول پروژه را تعریف کنید؛ پس از تأیید مدیر برگردید و دعوت کنید.">
            <x-slot:action>
                <x-button :href="route('market.client.create')" variant="primary" icon="plus">تعریف پروژه</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-card>
            <form method="POST" action="{{ route('market.invite.store', $providerId) }}" class="flex flex-col gap-5">
                @csrf
                <fieldset>
                    <legend class="mb-3 text-label font-semibold text-ink">کدام پروژه؟</legend>
                    <div class="flex flex-col gap-1">
                        @foreach ($projects as $project)
                            <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                                <input type="radio" name="project" value="{{ $project->uuid }}" required @checked($loop->first) class="size-5 shrink-0 accent-primary">
                                {{ $project->title }}@if ($project->is_private) (خصوصی)@endif
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" variant="primary">فرستادن دعوت</x-button>
                    <x-button :href="$provider['url']" variant="ghost">بازگشت به صفحه {{ $provider['laboratory'] ? 'آزمایشگاه' : 'مشاور' }}</x-button>
                </div>
            </form>
        </x-card>
    @endif

</x-layouts.workspace>
