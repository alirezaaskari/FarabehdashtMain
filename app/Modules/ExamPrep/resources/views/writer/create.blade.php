<x-layouts.workspace title="سؤال تازه آزمون"
                     heading="سؤال تازه آزمون"
                     lede="سؤال چهارگزینه‌ای با توضیح پاسخ و پیوند مطالعه. پیش از انتشار، مدیر بررسی‌اش می‌کند."
                     nav="exam-questions" help="exam-questions">

    @if ($errors->any())
        <x-alert tone="error" title="سؤال ثبت نشد" class="mb-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-alert>
    @endif

    @if ($packs->isEmpty())
        <x-empty-state icon="list" title="هنوز بسته‌ای ساخته نشده"
                       description="بسته‌ها را مدیر می‌سازد؛ پس از ساخته‌شدن، این‌جا موضوع‌هایش را می‌بینید." />
    @else
        <form method="POST" action="{{ route('exam_prep.writer.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="flex flex-col gap-2">
                <label for="topic" class="text-label font-semibold text-ink">بسته و موضوع</label>
                <select id="topic" name="topic" required
                        class="h-field rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                    @foreach ($packs as $pack)
                        <optgroup label="{{ $pack->title }}">
                            @foreach ($pack->topics as $topic)
                                <option value="{{ $topic->id }}" @selected((int) old('topic') === $topic->id)>{{ $topic->title }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <fieldset class="flex flex-col gap-1">
                <legend class="mb-1 text-label font-semibold text-ink">سختی</legend>
                <div class="flex flex-wrap gap-x-6">
                    @foreach ($difficulties as $difficulty)
                        <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label text-body">
                            <input type="radio" name="difficulty" value="{{ $difficulty->value }}" class="size-5 accent-primary"
                                   @checked(old('difficulty', 'medium') === $difficulty->value)>
                            {{ $difficulty->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex flex-col gap-2">
                <label for="body" class="text-label font-semibold text-ink">متن سؤال</label>
                <textarea id="body" name="body" rows="4" required
                          class="rounded-md border border-line-strong bg-surface px-3.5 py-3 text-control text-ink">{{ old('body') }}</textarea>
            </div>

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-1 text-label font-semibold text-ink">گزینه‌ها — گزینه درست را علامت بزنید</legend>
                <p class="text-note text-muted">دست‌کم دو گزینه؛ خانه خالی نادیده گرفته می‌شود.</p>
                @for ($i = 1; $i <= 5; $i++)
                    <div class="flex items-center gap-3">
                        <input type="radio" name="correct" value="{{ $i }}" id="correct-{{ $i }}" class="size-5 shrink-0 accent-primary"
                               @checked((int) old('correct') === $i)>
                        <label for="correct-{{ $i }}" class="sr-only">گزینه @fa($i) درست است</label>
                        <input type="text" name="choices[]" value="{{ old('choices.'.($i - 1)) }}" aria-label="گزینه @fa($i)"
                               class="h-field min-w-0 grow rounded-md border border-line-strong bg-surface px-3.5 text-control text-ink">
                    </div>
                @endfor
            </fieldset>

            <div class="flex flex-col gap-2">
                <label for="explanation" class="text-label font-semibold text-ink">توضیح پاسخ</label>
                <textarea id="explanation" name="explanation" rows="3"
                          class="rounded-md border border-line-strong bg-surface px-3.5 py-3 text-control text-ink">{{ old('explanation') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="reference_label" label="عنوان پیوند مطالعه" :value="old('reference_label')" hint="مثلاً «مقاله صدا در دانشنامه»" />
                <x-field name="reference_path" label="نشانی داخلی مطالعه" :value="old('reference_path')" numeric
                         hint="فقط صفحه‌ای از خود فرابهداشت، مثل /encyclopedia/noise" />
            </div>

            <div>
                <x-button type="submit" variant="primary">فرستادن برای بررسی</x-button>
            </div>
        </form>
    @endif

</x-layouts.workspace>
