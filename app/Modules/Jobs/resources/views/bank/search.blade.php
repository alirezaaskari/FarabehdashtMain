@php
    use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
@endphp

<x-layouts.workspace art="talent-search" title="بانک رزومه" heading="بانک رزومه"
                     lede="کارجوهایی که خودشان خواسته‌اند دیده شوند. کارت‌ها ناشناس‌اند؛ درخواست تماس بفرستید و اگر کارجو بپذیرد، نام و راه تماسش را می‌بینید."
                     nav="talent" help="talent-search">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('bank')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="results-heading" class="min-w-0">
            <form method="GET" action="{{ route('jobs.talent.index') }}" class="grid gap-4 rounded-xl border border-line bg-surface p-5 md:grid-cols-[1fr_1fr_10rem_auto] md:items-end">
                <div>
                    <label for="talent-skill" class="mb-2 block text-label font-semibold text-ink">مهارت</label>
                    <select id="talent-skill" name="skill" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        <option value="">همه مهارت‌ها</option>
                        @foreach ($skills as $term)
                            <option value="{{ $term->id }}" @selected($filters['skill'] === $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="talent-city" class="mb-2 block text-label font-semibold text-ink">شهر</label>
                    <select id="talent-city" name="city" class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                        <option value="">همه شهرها</option>
                        @foreach ($regions as $region)
                            <optgroup label="{{ $region['name'] }}">
                                @foreach ($region['cities'] as $key => $name)
                                    <option value="{{ $key }}" @selected($filters['city'] === $key)>{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="talent-years" class="mb-2 block text-label font-semibold text-ink">کمینه سابقه (سال)</label>
                    <input id="talent-years" name="years" type="number" inputmode="numeric" min="0" max="40" value="{{ $filters['years'] }}" data-numeric
                           class="h-field w-full rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                </div>
                <x-button type="submit" variant="secondary" icon="search">جست‌وجو</x-button>
            </form>

            <h2 id="results-heading" class="mt-6 text-h3 text-ink">@fa($cards->total()) کارجو</h2>

            @if ($cards->isEmpty())
                <x-empty-state art="empty-talent" icon="search" class="mt-4" title="کارجویی با این پالایش در بانک نیست"
                               description="پالایش را بازتر کنید، یا آگهی شغلی بگذارید تا کارجوها خودشان درخواست بفرستند." />
            @else
                <ul class="mt-4 grid list-none gap-4 ps-0 lg:grid-cols-2">
                    @foreach ($cards as $card)
                        @php($status = $asked[$card['user_id']] ?? null)
                        <li>
                            @component('jobs::bank._card', ['card' => $card])
                                <div class="mt-auto border-t border-line pt-4">
                                    @if ($status !== null && $status !== BankRequestStatus::Expired)
                                        <p class="text-label text-body">درخواست شما: <x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge></p>
                                    @elseif ($charging && $credits < 1)
                                        <p class="text-note text-muted">برای درخواست تماس، بسته اعتبار بخرید.</p>
                                    @else
                                        <form method="POST" action="{{ route('jobs.talent.contact', $card['token']) }}" class="flex flex-col gap-3">
                                            @csrf
                                            <label for="note-{{ $card['token'] }}" class="text-label font-semibold text-ink">پیام کوتاه به کارجو (اختیاری)</label>
                                            <textarea id="note-{{ $card['token'] }}" name="note" rows="2" maxlength="500"
                                                      class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink"
                                                      placeholder="مثلاً: برای واحد HSE کارخانه‌مان در اصفهان دنبال کارشناس صدا هستیم."></textarea>
                                            <div><x-button type="submit" variant="primary" size="sm">درخواست تماس</x-button></div>
                                        </form>
                                    @endif
                                </div>
                            @endcomponent
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $cards->links() }}</div>
            @endif
        </section>

        <div class="flex flex-col gap-6">
            @include('jobs::bank._billing')
            <x-button :href="route('jobs.talent.requests')" variant="secondary" block>درخواست‌های فرستاده‌شده</x-button>
        </div>
    </div>

</x-layouts.workspace>
