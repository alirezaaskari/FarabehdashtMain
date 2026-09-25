@php use App\Support\JalaliDate; use App\Modules\Expert\Domain\Enums\ReviewStatus; @endphp

<x-layouts.public :seo="$seo" active="expert">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', Route::has('home') ? route('home') : '/'], ['پرسش از متخصص', route('expert.index')], [$question->title, null]]" />
    </x-slot:breadcrumb>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($errors->has('accept'))
        <div class="mb-6"><x-alert tone="error">{{ $errors->first('accept') }}</x-alert></div>
    @endif

    @if ($isAsker && $question->status !== ReviewStatus::Published)
        <div class="mb-6">
            @if ($question->status === ReviewStatus::Rejected)
                <x-alert tone="caution" title="این پرسش منتشر نشد">{{ $question->review_note }}</x-alert>
            @else
                <x-alert tone="info">پرسش شما در انتظار تأیید مدیر است و فعلاً فقط شما آن را می‌بینید.</x-alert>
            @endif
        </div>
    @endif

    <article class="max-w-[46rem]">
        <header>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge>{{ $question->topic->label() }}</x-badge>
                @if ($question->isAnswered())
                    <x-badge tone="primary" icon="check">پاسخ‌گرفته</x-badge>
                @endif
                @if ($question->visibility->value === 'private')
                    <x-badge icon="lock">{{ $question->visibility->label() }}</x-badge>
                @endif
            </div>
            <h1 class="mt-3 text-h1 text-ink">{{ $question->title }}</h1>
            <p class="mt-2 text-note text-muted">
                پرسیده‌شده در {{ JalaliDate::long($question->published_at ?? $question->created_at) }} · نام پرسش‌کننده نمایش داده نمی‌شود
            </p>
        </header>

        <div class="mt-6">
            @foreach ($questionBody as $segments)
                <p class="mt-4 text-lede text-body"><x-linked-text :segments="$segments" /></p>
            @endforeach
        </div>

        <section aria-labelledby="answers-heading" class="mt-10">
            <h2 id="answers-heading" class="text-h2 text-ink">
                @if ($answers->isEmpty()) پاسخ‌ها @else @fa($answers->count()) پاسخ @endif
            </h2>

            @if ($answers->isEmpty())
                <p class="mt-3 text-copy text-muted">
                    @if ($question->status === ReviewStatus::Published)
                        هنوز پاسخی منتشر نشده. پاسخ مشاوران پس از تأیید مدیر این‌جا می‌آید.
                    @else
                        پس از تأیید پرسش، مشاوران پاسخ می‌دهند.
                    @endif
                </p>
            @endif

            @foreach ($answers as $answer)
                @php $accepted = $answer->id === $question->accepted_answer_id; @endphp
                <div id="answer-{{ $answer->uuid }}"
                     @class([
                         'mt-5 rounded-xl border bg-surface px-5 py-5',
                         'border-primary' => $accepted,
                         'border-line' => ! $accepted,
                     ])>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-label font-bold text-ink">{{ $answer->answererName() }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <x-badge tone="primary" icon="shield">مشاور تأییدشده در فرابهداشت</x-badge>
                                @if ($accepted)
                                    <x-badge tone="primary" icon="check">بهترین پاسخ به انتخاب پرسش‌کننده</x-badge>
                                @endif
                            </div>
                        </div>
                        <span class="text-note text-muted">{{ JalaliDate::short($answer->published_at ?? $answer->created_at) }}</span>
                    </div>

                    <div class="mt-3">
                        @foreach ($answerBodies[$answer->id] ?? [] as $segments)
                            <p class="mt-3 text-copy text-body"><x-linked-text :segments="$segments" /></p>
                        @endforeach
                    </div>

                    <x-disclaimer class="mt-4">این پاسخ نظر کارشناسی است؛ تشخیص پزشکی یا تأیید انطباق قانونی نیست.</x-disclaimer>

                    @if ($isAsker && ! $accepted)
                        <form method="POST" action="{{ route('expert.accept', [$question->uuid, $answer->uuid]) }}" class="mt-4">
                            @csrf
                            <x-button type="submit" variant="secondary" icon="check">این بهترین پاسخ است</x-button>
                        </form>
                    @endif
                </div>
            @endforeach
        </section>

        @if ($ownAnswer && $ownAnswer->status !== ReviewStatus::Published)
            <div class="mt-8">
                @if ($ownAnswer->status === ReviewStatus::Rejected)
                    <x-alert tone="caution" title="پاسخ شما برای اصلاح برگشت">{{ $ownAnswer->review_note }}</x-alert>
                @else
                    <x-alert tone="info">پاسخ شما در انتظار تأیید مدیر است.</x-alert>
                @endif
            </div>
        @endif

        @if ($canAnswer)
            <section aria-labelledby="answer-form-heading" class="mt-8 rounded-xl border border-line bg-surface px-5 py-5">
                <h2 id="answer-form-heading" class="text-h3 text-ink">پاسخ شما</h2>
                <p class="mt-1 text-note text-muted">
                    پاسخ پیش از انتشار بررسی می‌شود. ادعای تشخیص پزشکی، ایمنی قطعی یا انطباق قانونی قطعی ننویسید؛ منبع و روش را بگویید.
                </p>
                <form method="POST" action="{{ route('expert.answers.store', $question->uuid) }}" class="mt-4 flex flex-col gap-4">
                    @csrf
                    <div>
                        <label for="answer" class="sr-only">متن پاسخ</label>
                        <textarea id="answer" name="answer" rows="10" required
                                  @if ($errors->has('answer')) aria-invalid="true" @endif
                                  class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('answer', $ownAnswer?->body) }}</textarea>
                        @error('answer')
                            <p class="mt-1 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-button type="submit" variant="primary" icon="check">فرستادن پاسخ</x-button>
                    </div>
                </form>
            </section>
        @endif

        @guest
            <p class="mt-10 text-copy text-muted">
                پرسش خودتان را دارید؟
                <a href="{{ route('expert.create') }}" class="font-semibold">وارد شوید و بپرسید</a>.
            </p>
        @endguest
    </article>

</x-layouts.public>
