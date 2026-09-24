<x-layouts.workspace title="میزکار"
                     heading="میزکار"
                     :lede="$view->isPersonal()
                         ? 'خلاصه کار شما در همه بخش‌های فرابهداشت.'
                         : 'نمای '.$view->label.' — فقط آنچه به این نقش مربوط است.'"
                     nav="dashboard">

    @if (count($views) > 1)
        <form method="POST" action="{{ route('workspace.view') }}" class="mb-8">
            @csrf
            <fieldset>
                <legend class="mb-3 text-label font-bold text-muted">نمای میزکار</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($views as $option)
                        <button type="submit" name="view" value="{{ $option->key }}"
                                @class([
                                    'inline-flex min-h-touch items-center rounded-full border px-4 text-label font-bold',
                                    'border-transparent bg-primary text-on-primary' => $option->key === $view->key,
                                    'border-line bg-surface text-ink hover:bg-surface-2' => $option->key !== $view->key,
                                ])
                                @if ($option->key === $view->key) aria-pressed="true" @else aria-pressed="false" @endif>
                            {{ $option->label }}
                        </button>
                    @endforeach
                </div>
            </fieldset>

            @error('view')
                <p class="mt-3 text-note text-danger" role="alert">{{ $message }}</p>
            @enderror
        </form>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
        @foreach ($widgets as $widget)
            @include('workspace::partials.widget', ['widget' => $widget])
        @endforeach
    </div>

</x-layouts.workspace>
