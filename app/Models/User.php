<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\PanelAccess;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\Mobile;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * حساب کاربر.
 *
 * تصمیم معماری: User زیرساخت مشترک است، نه دارایی یک ماژول. تنها استثنای
 * قاعده «هیچ ماژولی Model ماژول دیگر را import نمی‌کند» همین کلاس است؛
 * ماژول Identity صاحب پروفایل‌ها، ورود و مجوزهاست، نه صاحب خود حساب.
 *
 * هر انسان دقیقاً یک حساب دارد. نقش‌های تجاری Profile هستند، نه حساب دوم.
 *
 * @property int $id
 * @property string|null $name
 * @property string $mobile
 * @property Carbon|null $mobile_verified_at
 * @property string|null $email
 * @property string|null $national_code
 * @property string|null $national_code_hash
 * @property UserStatus $status
 * @property Carbon|null $onboarded_at
 * @property Collection<int, UserProfile> $profiles
 */
final class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'password',
        'national_code',
        'national_code_hash',
        'status',
        'mobile_verified_at',
        'onboarded_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'national_code',
        'national_code_hash',
    ];

    /** @return HasMany<UserProfile, $this> */
    public function profiles(): HasMany
    {
        return $this->hasMany(UserProfile::class);
    }

    /**
     * پروفایل‌هایی که واقعاً دسترسی اضافه می‌کنند.
     *
     * @return Collection<int, UserProfile>
     */
    public function activeProfiles(): Collection
    {
        return $this->profiles->filter(fn (UserProfile $profile): bool => $profile->grantsAccess())->values();
    }

    public function profileFor(ProfileType $type): ?UserProfile
    {
        return $this->profiles->firstWhere('type', $type);
    }

    public function hasActiveProfile(ProfileType $type): bool
    {
        return $this->profileFor($type)?->grantsAccess() ?? false;
    }

    public function profileStatus(ProfileType $type): ?ProfileStatus
    {
        return $this->profileFor($type)?->status;
    }

    public function canSignIn(): bool
    {
        return $this->status->canSignIn();
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarded_at !== null;
    }

    /**
     * نام نمایشی.
     *
     * ثبت‌نام فقط با موبایل است و `name` می‌تواند خالی بماند تا مرحله تکمیل
     * پروفایل. Filament نام را رشته اجباری می‌خواهد، پس شماره نیمه‌پوشیده
     * جایگزین می‌شود — نه شماره کامل، چون این نام بالای هر صفحه پنل دیده
     * می‌شود و جای نمایش داده تماس نیست.
     */
    public function getFilamentName(): string
    {
        $name = trim((string) $this->name);

        if ($name !== '') {
            return $name;
        }

        return Mobile::tryFromInput($this->mobile)?->masked() ?? 'کاربر';
    }

    /**
     * Filament این را می‌پرسد، ولی جوابش به ماژول مدیریت مربوط است.
     *
     * اگر آن ماژول غیرفعال باشد هیچ پیاده‌سازی‌ای در کانتینر نیست و پاسخ «نه»
     * است — یعنی پنل عملاً برای هیچ‌کس باز نمی‌شود. پیش‌فرض امن همین است.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! app()->bound(PanelAccess::class)) {
            return false;
        }

        return app(PanelAccess::class)->canAccessPanel($this, $panel->getId());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'password' => 'hashed',
            'national_code' => 'encrypted',
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }
}
