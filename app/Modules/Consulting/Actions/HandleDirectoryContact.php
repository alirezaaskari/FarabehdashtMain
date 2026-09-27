<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Models\User;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\DirectoryContact;
use App\Modules\Consulting\Events\DirectoryContactChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * درخواست تماس با آزمایشگاه و پاسخ آن (DEC-58). شماره درخواست‌دهنده فقط با
 * تیک خودش به همان آزمایشگاه نشان داده می‌شود؛ پاسخ در سایت می‌ماند.
 */
final readonly class HandleDirectoryContact
{
    public function __construct(private Dispatcher $events) {}

    public function request(User $user, ConsultantProfile $lab, string $message, ?string $service, ?string $city, bool $shareMobile): DirectoryContact
    {
        if (! $lab->isLaboratory() || ! $lab->isListed()) {
            throw new RuntimeException('درخواست تماس فقط برای آزمایشگاه‌های منتشرشده است.');
        }

        if ($lab->user_id === $user->getKey()) {
            throw new RuntimeException('به صفحه خودتان درخواست تماس نمی‌فرستید.');
        }

        $contact = DirectoryContact::query()->create([
            'uuid' => (string) Str::uuid7(),
            'profile_id' => $lab->id,
            'user_id' => $user->getKey(),
            'service' => $service,
            'city' => $city,
            'message' => trim($message),
            'share_mobile' => $shareMobile,
        ]);

        $this->events->dispatch(new DirectoryContactChanged($contact, DirectoryContactChanged::REQUESTED, (int) $user->getKey()));

        return $contact;
    }

    public function reply(User $user, DirectoryContact $contact, string $reply): DirectoryContact
    {
        if ($contact->profile->user_id !== $user->getKey()) {
            throw new RuntimeException('فقط همان آزمایشگاه پاسخ می‌دهد.');
        }

        if ($contact->replied_at !== null) {
            throw new RuntimeException('به این درخواست پاسخ داده‌اید.');
        }

        $reply = trim($reply) !== '' ? trim($reply) : throw new RuntimeException('متن پاسخ را بنویسید.');

        $contact->forceFill(['reply' => $reply, 'replied_at' => Carbon::now()])->save();

        $this->events->dispatch(new DirectoryContactChanged($contact, DirectoryContactChanged::REPLIED, (int) $user->getKey()));

        return $contact;
    }
}
