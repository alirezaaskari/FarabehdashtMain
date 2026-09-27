<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Contracts\MediaLibrary;
use App\Models\User;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use App\Modules\Consulting\Domain\ProfileDraft;
use App\Modules\Consulting\Events\ConsultantProfileSubmitted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مشاور صفحه‌اش را برای تأیید مدیر می‌فرستد.
 *
 * نسخه منتشرشده دست نمی‌خورد؛ ویرایش در `pending` می‌نشیند. نشانی صفحه
 * پس از نخستین انتشار ثابت است تا پیوندهای بیرونی و نتیجه جست‌وجو نشکنند.
 */
final readonly class SubmitConsultantProfile
{
    public function __construct(
        private MediaLibrary $media,
        private Dispatcher $events,
    ) {}

    public function handle(User $user, ProfileDraft $draft, ?UploadedFile $photo = null): ConsultantProfile
    {
        $kind = self::kindOf($user);

        $profile = ConsultantProfile::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['uuid' => (string) Str::uuid7(), 'status' => ProfileReviewStatus::Draft],
        );

        // نوع فقط پیش از نخستین انتشار عوض می‌شود؛ نشانی صفحه منتشرشده به آن بسته است.
        if ($profile->published_at === null) {
            $profile->kind = $kind;
        }

        $slug = $profile->published_at === null ? $draft->slug : (string) $profile->slug;

        if (ConsultantProfile::query()->where('slug', $slug)->whereKeyNot($profile->getKey())->exists()) {
            throw new RuntimeException('این نشانی را صفحه دیگری گرفته است.');
        }

        $photoId = $photo === null
            ? $draft->photoId
            : $this->media->storeImage($photo->getRealPath() ?: $photo->getPathname(), $photo->getClientOriginalName(), (int) $user->getKey())->id;

        $pending = new ProfileDraft(
            slug: $slug,
            displayName: $draft->displayName,
            headline: $draft->headline,
            bio: $draft->bio,
            province: $draft->province,
            city: $draft->city,
            experience: $draft->experience,
            education: $draft->education,
            domainIds: $draft->domainIds,
            photoId: $photoId,
            offerings: $draft->offerings,
        );

        $profile->forceFill([
            'slug' => $slug,
            'pending' => $pending->toArray(),
            'status' => ProfileReviewStatus::Pending,
            'submitted_at' => Carbon::now(),
            'review_note' => null,
        ])->save();

        $this->events->dispatch(new ConsultantProfileSubmitted($profile));

        return $profile;
    }

    /** مشاور اگر نقش مشاور دارد، وگرنه آزمایشگاه اگر نقشش تأیید شده. */
    public static function kindOf(User $user): ProviderKind
    {
        return match (true) {
            $user->can('consulting.services.manage') => ProviderKind::Consultant,
            $user->can('directory.contacts.manage') => ProviderKind::Laboratory,
            default => throw new RuntimeException('صفحه عمومی فقط برای کسی است که نقش مشاور یا آزمایشگاهش تأیید شده.'),
        };
    }
}
