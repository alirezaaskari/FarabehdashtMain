<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Modules\Webinars\Events\WebinarSaved;
use App\Support\Money;
use App\Support\PersianDigits;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * ساخت یا ویرایش رویداد از پنل.
 *
 * DEC-48: پیوند جلسه باید https باشد و فقط به سرویس برگزاری اشاره کند؛ هیچ
 * داده کاربری در آن گذاشته نمی‌شود. نشانی ضبط، مسیر داخلی سایت است (مثلاً
 * دوره‌ای که از ضبط جلسه ساخته شده).
 */
final readonly class SaveWebinar
{
    public function __construct(private Dispatcher $events) {}

    /**
     * @param  array{title: string, slug: string, description: string, instructor_name: string, starts_at: string, duration_minutes: string, capacity: string, price: string, join_url: string, recording_url: string}  $input
     */
    public function handle(array $input, int $actorId, ?Webinar $webinar = null): Webinar
    {
        $title = trim($input['title']);
        $description = trim($input['description']);
        $instructor = trim($input['instructor_name']);
        $slug = Str::lower(trim($input['slug']));
        $duration = (int) PersianDigits::toLatin(trim($input['duration_minutes']));
        $capacity = (int) PersianDigits::toLatin(trim($input['capacity']));
        $price = trim($input['price']) === '' ? Money::zero() : Money::fromInput($input['price']);
        $joinUrl = trim($input['join_url']);
        $recording = trim($input['recording_url']);

        if (mb_strlen($title) < 3 || mb_strlen($description) < 10 || mb_strlen($instructor) < 3) {
            throw new InvalidArgumentException('عنوان، معرفی و نام مدرس را کامل بنویسید.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException('نشانی رویداد فقط حروف کوچک لاتین، عدد و خط تیره است؛ مثل noise-webinar.');
        }

        if (Webinar::query()->where('slug', $slug)->when($webinar, fn ($q) => $q->whereKeyNot($webinar->id))->exists()) {
            throw new InvalidArgumentException('این نشانی را رویداد دیگری دارد.');
        }

        try {
            $startsAt = Carbon::parse(PersianDigits::toLatin(trim($input['starts_at'])));
        } catch (Throwable) {
            throw new InvalidArgumentException('زمان شروع را کامل انتخاب کنید.');
        }

        if ($duration < 15 || $duration > 600) {
            throw new InvalidArgumentException('مدت رویداد بین ۱۵ و ۶۰۰ دقیقه است.');
        }

        if ($capacity < 1 || $capacity > 10_000) {
            throw new InvalidArgumentException('ظرفیت بین ۱ و ۱۰٬۰۰۰ نفر است.');
        }

        if (filter_var($joinUrl, FILTER_VALIDATE_URL) === false || ! str_starts_with($joinUrl, 'https://')) {
            throw new InvalidArgumentException('پیوند جلسه باید نشانی کامل https سرویس برگزاری باشد.');
        }

        if ($recording !== '' && preg_match('#^/[A-Za-z0-9/_\-]*$#', $recording) !== 1) {
            throw new InvalidArgumentException('نشانی ضبط باید مسیری داخل خود سایت باشد؛ مثل /courses/noise-basics.');
        }

        $before = $webinar === null ? null : ['starts_at' => $webinar->starts_at->toIso8601String(), 'price_toman' => $webinar->price_toman, 'capacity' => $webinar->capacity];

        $webinar ??= new Webinar(['uuid' => (string) Str::uuid7(), 'status' => WebinarStatus::Draft]);
        $webinar->fill([
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'instructor_name' => $instructor,
            'starts_at' => $startsAt,
            'duration_minutes' => $duration,
            'capacity' => $capacity,
            'price_toman' => $price->toman,
            'join_url' => $joinUrl,
            'recording_url' => $recording === '' ? null : $recording,
        ])->save();

        $this->events->dispatch(new WebinarSaved($webinar, $before, $actorId));

        return $webinar;
    }
}
