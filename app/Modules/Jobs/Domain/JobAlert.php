<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * هشدار شغل یک کاربر: شهر، مهارت و نوع همکاری (هر کدام اختیاری) یا تطبیق با
 * مهارت‌های گذرنامه. پیامک اختیاری است و پیش‌فرض خاموش (DEC-73).
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $city
 * @property int|null $skill_id
 * @property EmploymentType|null $employment_type
 * @property bool $match_passport
 * @property bool $sms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class JobAlert extends Model
{
    protected $fillable = [
        'user_id',
        'city',
        'skill_id',
        'employment_type',
        'match_passport',
        'sms',
    ];

    /**
     * آیا آگهی با این هشدار جور است.
     *
     * @param  list<int>  $postingSkillIds
     * @param  list<int>  $passportSkillIds  مهارت‌های گذرنامه صاحب هشدار
     */
    public function matches(JobPosting $posting, array $postingSkillIds, array $passportSkillIds): bool
    {
        return ($this->city === null || $this->city === $posting->city)
            && ($this->employment_type === null || $this->employment_type === $posting->employment_type)
            && ($this->skill_id === null || in_array($this->skill_id, $postingSkillIds, true))
            && (! $this->match_passport || array_intersect($postingSkillIds, $passportSkillIds) !== []);
    }

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'match_passport' => 'boolean',
            'sms' => 'boolean',
            'skill_id' => 'integer',
        ];
    }
}
