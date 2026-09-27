@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace art="team-hub"
                     title="تیم"
                     heading="تیم"
                     lede="اشتراک تیمی شما: صندلی‌ها، اعضا و دعوت‌ها. صاحب تیم به داده اعضا دسترسی ندارد."
                     nav="team"
                     help="team">

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif
    @error('team')
        <div class="mb-6"><x-alert tone="error">{{ $message }}</x-alert></div>
    @enderror

    @if ($received->isNotEmpty())
        <section aria-labelledby="received-heading" class="mb-10">
            <h2 id="received-heading" class="text-h3 text-ink">دعوت‌های رسیده</h2>
            <ul class="mt-4 flex list-none flex-col gap-3 ps-0">
                @foreach ($received as $invitation)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-h4 text-ink">{{ $invitation->team->name }}</p>
                            <p class="mt-1 text-note text-muted">
                                دعوت از {{ $invitation->team->owner->getFilamentName() }} ·
                                {{ $invitation->team->isCurrent() ? 'اعتبار تیم تا '.JalaliDate::long($invitation->team->ends_at) : 'اشتراک این تیم تمام شده' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('monetization.team.invitations.accept', $invitation->uuid) }}">
                                @csrf
                                <x-button type="submit" variant="primary">پذیرفتن</x-button>
                            </form>
                            <form method="POST" action="{{ route('monetization.team.invitations.decline', $invitation->uuid) }}">
                                @csrf
                                <x-button type="submit" variant="secondary">رد</x-button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-note text-muted">با پذیرفتن، صاحب تیم فقط می‌بیند که عضو شده‌اید؛ محاسبه‌ها، پروژه‌ها و گزارش‌های شما مال خودتان می‌ماند.</p>
        </section>
    @endif

    @if ($seat !== null)
        <section aria-labelledby="membership-heading" class="mb-10">
            <h2 id="membership-heading" class="text-h3 text-ink">عضویت شما</h2>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface px-5 py-4">
                <div class="min-w-0">
                    <p class="text-h4 text-ink">{{ $seat->team->name }}</p>
                    <p class="mt-1 text-note text-muted">
                        {{ $seat->team->isCurrent() ? 'امکانات حرفه‌ای تا '.JalaliDate::long($seat->team->ends_at).' برای شما باز است.' : 'اشتراک این تیم تمام شده است.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-button :href="route('monetization.team.library')" variant="secondary" icon="file">کتابخانه تیم</x-button>
                    <form method="POST" action="{{ route('monetization.team.leave') }}">
                        @csrf
                        <x-button type="submit" variant="secondary">خروج از تیم</x-button>
                    </form>
                </div>
            </div>
        </section>
    @endif

    @if ($team === null || $team->ends_at === null)
        @if ($seat === null && $received->isEmpty())
            <x-empty-state art="empty-team" icon="user"
                           title="هنوز تیمی ندارید"
                           description="اگر همکارانتان هم از فرابهداشت استفاده می‌کنند، برای همه با یک صورتحساب اشتراک بخرید. اگر کسی شما را دعوت کند، دعوتش همین‌جا می‌آید.">
                @if ($canBuy)
                    <x-slot:action>
                        <x-button :href="route('monetization.team.buy')" variant="primary">خرید برای تیم</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @elseif ($canBuy)
            <p class="text-copy text-muted">برای تیم خودتان هم می‌توانید <a href="{{ route('monetization.team.buy') }}" class="inline-flex min-h-touch items-center">اشتراک تیم بخرید</a>.</p>
        @endif
    @else
        <section aria-labelledby="team-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="team-heading" class="text-h3 text-ink">تیم «{{ $team->name }}»</h2>
                <div class="flex flex-wrap gap-2">
                    <x-button :href="route('monetization.team.library')" variant="secondary" icon="file">کتابخانه تیم</x-button>
                    @if ($canBuy)
                        <x-button :href="route('monetization.team.buy')" variant="secondary">تمدید یا تغییر صندلی</x-button>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <x-stat label="صندلی" :value="\App\Support\PersianNumber::format($team->seat_count)" :numeric="false" />
                <x-stat label="صندلی خالی" :value="\App\Support\PersianNumber::format($team->freeSeats())" :numeric="false" />
                <x-stat label="اعتبار تا" :value="JalaliDate::short($team->ends_at)" :numeric="false" />
            </div>

            @unless ($team->isCurrent())
                <x-alert tone="caution" class="mt-5">اشتراک تیم تمام شده است؛ اعضا تا تمدید به پلن رایگان برگشته‌اند.</x-alert>
            @endunless

            @if ($team->isCurrent())
                <form method="POST" action="{{ route('monetization.team.invite') }}" class="mt-8 flex flex-wrap items-end gap-3">
                    @csrf
                    <x-field name="mobile" label="دعوت با شماره موبایل" type="tel" :value="old('mobile')" numeric inputmode="numeric" dir="ltr"
                             :error="$errors->first('mobile')" hint="صاحب شماره در صفحه «تیم» خودش دعوت را می‌بیند و می‌پذیرد." class="sm:w-80" />
                    <x-button type="submit" variant="primary" :disabled="$team->freeSeats() < 1">دعوت</x-button>
                </form>
            @endif

            <h3 class="mt-10 text-h4 text-ink">اعضا</h3>
            <ul class="mt-3 flex list-none flex-col gap-2 ps-0">
                <li class="flex min-h-touch items-center justify-between gap-3 border-b border-line py-2">
                    <span class="text-label text-ink">شما (صاحب تیم)</span>
                </li>
                @foreach ($members as $member)
                    <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 border-b border-line py-2">
                        <span class="text-label text-ink">{{ $member->member->getFilamentName() }} <span class="text-note text-muted">· از {{ JalaliDate::short($member->granted_at) }}</span></span>
                        <form method="POST" action="{{ route('monetization.team.members.remove', $member->id) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">پس گرفتن صندلی</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>

            @if ($invitations->isNotEmpty())
                <h3 class="mt-10 text-h4 text-ink">دعوت‌های در انتظار</h3>
                <ul class="mt-3 flex list-none flex-col gap-2 ps-0">
                    @foreach ($invitations as $invitation)
                        <li class="flex min-h-touch flex-wrap items-center justify-between gap-3 border-b border-line py-2">
                            <span class="text-label text-ink"><span dir="ltr" data-numeric>{{ $invitation->mobile }}</span> <span class="text-note text-muted">· {{ JalaliDate::short($invitation->created_at) }}</span></span>
                            <form method="POST" action="{{ route('monetization.team.invitations.cancel', $invitation->uuid) }}">
                                @csrf
                                <x-button type="submit" variant="secondary">لغو دعوت</x-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

</x-layouts.workspace>
