<x-layouts.workspace art="identity-welcome" title="خوش آمدید"
                     heading="خوش آمدید"
                     lede="دو قدم کوتاه تا شروع: اگر خواستید نامتان را بنویسید و بگویید برای چه آمده‌اید تا همان بخش را باز کنیم."
                     help="welcome">

    <form method="POST" action="{{ route('identity.welcome.store') }}" class="flex flex-col gap-8">
        @csrf

        <x-field name="name" label="نام و نام خانوادگی (اختیاری)" autocomplete="name" class="lg:w-1/2"
                 :value="old('name', $user->name)" :error="$errors->first('name')"
                 hint="در گزارش‌ها و گواهی دوره‌ها همین نام می‌آید. بعداً در «حساب من» هم عوض می‌شود." />

        <fieldset>
            <legend class="text-h4 text-ink">برای چه آمده‌اید؟</legend>
            @if ($errors->has('goal'))
                <p class="mt-2 text-note text-danger">{{ $errors->first('goal') }}</p>
            @endif

            <ul class="mt-4 divide-y divide-line border-y border-line">
                @foreach ($goals as $key => $goal)
                    <li>
                        <button type="submit" name="goal" value="{{ $key }}"
                                class="flex min-h-touch w-full flex-col items-start gap-1 py-4 text-start hover:bg-surface-2 focus-visible:bg-surface-2">
                            <span class="text-label text-ink">{{ $goal['label'] }}</span>
                            <span class="text-note text-muted">{{ $goal['text'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </fieldset>

        <div>
            <x-button type="submit" name="goal" value="skip" variant="secondary">بعداً؛ برو به میزکار</x-button>
        </div>
    </form>

</x-layouts.workspace>
