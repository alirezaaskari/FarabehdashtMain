<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Tests;

use App\Models\User;
use App\Modules\Webinars\Actions\ChangeWebinarStatus;
use App\Modules\Webinars\Actions\SaveWebinar;
use App\Modules\Webinars\Domain\Webinar;

trait WebinarFixtures
{
    /** @param  array<string, string>  $overrides */
    private function webinar(array $overrides = [], bool $publish = true): Webinar
    {
        $admin = User::factory()->create();

        $webinar = $this->app->make(SaveWebinar::class)->handle([
            'title' => 'ارزیابی صدا در کارگاه',
            'slug' => 'noise-webinar',
            'description' => 'اندازه‌گیری و تفسیر تراز صدا در کارگاه‌های کوچک.',
            'instructor_name' => 'مهندس نمونه',
            'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'duration_minutes' => '90',
            'capacity' => '2',
            'price' => '50000',
            'join_url' => 'https://www.skyroom.online/ch/fbh/noise',
            'recording_url' => '',
            ...$overrides,
        ], $admin->id);

        if ($publish) {
            $this->app->make(ChangeWebinarStatus::class)->publish($webinar, $admin->id);
        }

        return $webinar->refresh();
    }
}
