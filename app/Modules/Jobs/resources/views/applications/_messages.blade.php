@php use App\Support\JalaliDate; @endphp

<x-card title="گفت‌وگو" heading="text-h4">
    @if ($application->messages->isEmpty())
        <p class="text-copy text-muted">هنوز پیامی نیست. پرسش درباره شغل، زمان مصاحبه یا مدرک تکمیلی را همین‌جا بنویسید.</p>
    @else
        <ul class="flex list-none flex-col gap-3 ps-0">
            @foreach ($application->messages as $message)
                @php $mine = $message->user_id === auth()->id(); @endphp
                <li @class(['rounded-lg px-4 py-3', 'bg-primary-soft' => $mine, 'bg-surface-2' => ! $mine])>
                    <p class="text-note font-semibold text-muted">{{ $mine ? 'شما' : ($message->user_id === $application->user_id ? 'کارجو' : 'کارفرما') }} · {{ JalaliDate::long($message->created_at) }}</p>
                    <p class="mt-1 whitespace-pre-line text-copy text-body">{{ $message->body }}</p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($application->status->allowsMessages())
        <form method="POST" action="{{ $action }}" class="mt-4 flex flex-col gap-3">
            @csrf
            <label for="body" class="text-label font-semibold text-ink">پیام تازه</label>
            <textarea id="body" name="body" rows="3" required
                      class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('body') }}</textarea>
            @error('body')
                <p class="text-note font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
            <div><x-button type="submit" variant="secondary">فرستادن</x-button></div>
        </form>
    @endif
</x-card>
