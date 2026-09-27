{{-- بخش «به اظهار خود کاربر»: بی‌نشان، با همین برچسب. --}}
@foreach ($kinds as $kind)
    @php $entries = $passport->entriesOf($kind); @endphp
    @if ($entries->isNotEmpty())
        <x-card :title="$kind->label()" heading="text-h4">
            <ul class="flex list-none flex-col divide-y divide-line ps-0">
                @foreach ($entries as $entry)
                    <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-label font-semibold text-ink">{{ $entry->title }}</p>
                            <p class="text-note text-muted">
                                {{ $entry->organization }}@if ($entry->organization && $entry->years()) · @endif
                                @if ($entry->years()) <span>@fa($entry->years())</span> @endif
                            </p>
                            @if ($entry->note)
                                <p class="mt-1 whitespace-pre-line text-note text-body">{{ $entry->note }}</p>
                            @endif
                        </div>
                        @if ($editable ?? false)
                            <form method="POST" action="{{ route('jobs.passport.entries.destroy', $entry->id) }}">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="ghost" size="sm" aria-label="برداشتن {{ $entry->title }}">برداشتن</x-button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
@endforeach
