<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Support\Site\Shelves;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تا آگهی زنده‌ای نیست، کاریابی در منو و صفحه اصلی پیوند نمی‌گیرد؛ با اولین آگهی برمی‌گردد.
 */
final class JobShelfTest extends TestCase
{
    use JobFixtures;
    use RefreshDatabase;

    public function test_jobs_appear_in_the_menu_and_homepage_with_the_first_live_posting(): void
    {
        $this->get('/')->assertOk()->assertDontSee('دنبال کار می‌گردم')->assertDontSee('id="home-career"', false);

        $this->publish($this->companyOwner(), 'کارشناس بهداشت حرفه‌ای');
        $this->app->forgetScopedInstances();

        $this->assertTrue($this->app->make(Shelves::class)->open('jobs.index'));
        $this->get('/')->assertOk()->assertSee('دنبال کار می‌گردم')->assertSee('id="home-career"', false);
    }
}
