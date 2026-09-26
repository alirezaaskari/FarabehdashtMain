<x-layouts.public title="مقایسه مواد شیمیایی"
                  description="تا سه ماده را کنار هم بگذارید. هر مقایسه یک صفحه پایدار و ایندکس‌پذیر می‌سازد."
                  active="chemicals">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', route('home')],
            ['بانک مواد شیمیایی', route('chemicals.index')],
            ['مقایسه', null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header art="scene.lab" title="مقایسه مواد شیمیایی"
                   lede="تا سه ماده را کنار هم بگذارید. هر جفت ماده یک صفحه پایدار و ایندکس‌پذیر می‌سازد." />

    <x-card size="lg" class="mt-8">
        <form method="GET" action="{{ route('chemicals.compare') }}" class="flex flex-wrap items-end gap-3">
            @for ($i = 0; $i < $max; $i++)
                <label class="flex min-w-0 grow flex-col gap-1.5" style="flex-basis: 12rem;">
                    <span class="text-note font-semibold text-ink">
                        ماده {{ ['اول', 'دوم', 'سوم', 'چهارم', 'پنجم'][$i] ?? $i + 1 }}
                    </span>
                    <input type="search" name="terms[]" value="{{ $terms[$i] ?? '' }}"
                           placeholder="نام یا شماره CAS"
                           class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                </label>
            @endfor

            <x-button type="submit" variant="primary">مقایسه</x-button>
        </form>
    </x-card>

    @if ($notFound !== [])
        <x-alert tone="caution" class="mt-6">
            این عبارت‌ها در بانک منتشرشده پیدا نشدند: {{ implode('، ', $notFound) }}
        </x-alert>
    @endif

    <div class="mt-6">
        @if ($terms === [])
            <x-empty-state icon="chemical"
                           title="دو یا سه ماده را برای مقایسه وارد کنید"
                           description="مثلاً «تولوئن»، «زایلن» و «بنزن»." />
        @elseif (count($substances) < 2)
            <x-empty-state icon="chemical"
                           title="برای مقایسه دست‌کم دو ماده لازم است"
                           description="یک ماده به‌تنهایی چیزی برای مقایسه ندارد." />
        @else
            <x-data-table :headers="array_merge(['ویژگی'], collect($substances)->pluck('name_fa')->all())"
                          caption="مقایسه ویژگی‌ها و حدود مواجهه مواد انتخاب‌شده">
                @foreach ($rows as $row)
                    <tr>
                        <th scope="row" class="px-4 py-3.5 text-start text-label font-semibold text-muted">
                            {{ $row->label }}
                        </th>
                        @foreach ($row->values as $value)
                            <td @if ($row->numeric) dir="ltr" data-numeric @endif>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach

                <x-slot:footnote>
                    مقادیر این جدول از رکوردهای منبع‌دار بانک مواد خوانده می‌شود. این صفحه جایگزین SDS سازنده نیست.
                </x-slot:footnote>
            </x-data-table>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($substances as $substance)
                    <x-button :href="route('chemicals.show', $substance->slug)" variant="secondary" size="sm">
                        صفحه کامل {{ $substance->name_fa }}
                    </x-button>
                @endforeach
            </div>
        @endif
    </div>

    <x-disclaimer class="mt-8">
        این بانک مرجع آموزشی است و جایگزین برگه اطلاعات ایمنی (SDS) سازنده یا قضاوت کارشناسی نیست.
    </x-disclaimer>

</x-layouts.public>
