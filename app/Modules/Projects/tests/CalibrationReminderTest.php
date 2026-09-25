<?php

declare(strict_types=1);

namespace App\Modules\Projects\Tests;

use App\Models\User;
use App\Modules\Projects\Actions\RemindCalibrations;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * یادآور پایان کالیبراسیون (بخش ۱۸-۲): یک اعلان برای همه تجهیزات یک کاربر.
 */
final class CalibrationReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_notice_covers_every_device_due_soon(): void
    {
        $user = User::factory()->create();
        $this->equipment($user, Carbon::today()->addDays(10));
        $this->equipment($user, Carbon::today()->addDays(25));
        $this->equipment($user, Carbon::today()->addDays(90));

        $this->assertSame(1, $this->remind());
        $this->assertSame(0, $this->remind());

        $notification = UserNotification::query()->where('user_id', $user->id)->sole();
        $this->assertSame('projects.calibration_due', $notification->kind);
        $this->assertStringContainsString('۲ تجهیز', $notification->title);
        $this->assertSame(route('projects.equipment.index'), $notification->url());
    }

    public function test_a_new_calibration_date_earns_a_new_reminder(): void
    {
        $user = User::factory()->create();
        $device = $this->equipment($user, Carbon::today()->addDays(5));
        $this->remind();

        $device->update(['calibration_valid_until' => Carbon::today()->addYear()]);

        $this->assertSame(1, $this->remind(Carbon::today()->addYear()->subDays(7)));
    }

    public function test_already_expired_devices_are_left_to_their_badge(): void
    {
        $this->equipment(User::factory()->create(), Carbon::today()->subDay());

        $this->assertSame(0, $this->remind());
    }

    private function remind(?Carbon $at = null): int
    {
        return $this->app->make(RemindCalibrations::class)->handle($at);
    }

    private function equipment(User $user, Carbon $validUntil): Equipment
    {
        return Equipment::query()->create([
            'user_id' => $user->getKey(),
            'name' => 'صداسنج',
            'calibration_valid_until' => $validUntil,
        ]);
    }
}
