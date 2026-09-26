<x-layouts.workspace art="character.writer" title="نوشته‌های دانشنامه"
                     heading="نوشته‌های دانشنامه"
                     lede="پیش‌نویس بنویسید و برای بازبینی بفرستید؛ پس از تأیید مدیر در دانشنامه منتشر می‌شود."
                     nav="writing">

    <x-slot:actions>
        <x-button :href="route('encyclopedia.writing.create')" variant="primary" icon="plus">نوشته تازه</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="min-w-0">
            @if ($articles->isEmpty())
                <x-empty-state icon="book"
                               title="هنوز چیزی ننوشته‌اید"
                               description="نخستین پیش‌نویس را بنویسید؛ راهنمای کنار صفحه قدم‌به‌قدم می‌گوید چه چیزی لازم است.">
                    <x-slot:action>
                        <x-button :href="route('encyclopedia.writing.create')" variant="primary">نوشته تازه</x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <ul class="flex list-none flex-col gap-3.5 ps-0">
                    @foreach ($articles as $article)
                        <li class="rounded-xl border border-line bg-surface p-5">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-h4 text-ink">{{ $article->title }}</h2>
                                <x-badge :tone="$article->status->tone()">{{ $article->status->label() }}</x-badge>
                                <span class="text-note text-muted">{{ $article->type->label() }}</span>
                            </div>

                            @if ($article->review_note)
                                <div class="mt-3"><x-alert tone="caution" title="یادداشت مدیر">{{ $article->review_note }}</x-alert></div>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($article->status->publiclyVisible() && Route::has('encyclopedia.show'))
                                    <x-button :href="route('encyclopedia.show', $article->slug)" variant="secondary" size="sm">دیدن در دانشنامه</x-button>
                                @else
                                    <x-button :href="route('encyclopedia.writing.edit', $article->uuid)" variant="secondary" size="sm">
                                        {{ $article->status === \App\Modules\Encyclopedia\Domain\Enums\ArticleStatus::Draft ? 'ویرایش' : 'دیدن' }}
                                    </x-button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <x-card title="راهنمای نوشتن برای دانشنامه">
            @include('encyclopedia::writing.guide')
        </x-card>
    </div>

</x-layouts.workspace>
