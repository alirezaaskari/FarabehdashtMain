<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Filament\Pages\MarketReviewPage;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * کارفرما، مدیر و پروژه منتشرشده برای تست‌های بازار پروژه.
 */
trait MarketFixtures
{
    private const DESCRIPTION = 'اندازه‌گیری تراز فشار صوت در سه سالن نورد گرم در دو شیفت، رسم نقشه صدا و گزارش با پیشنهاد کنترل مهندسی و برنامه حفاظت شنوایی.';

    /** @param  array<string, mixed>  $overrides */
    private function projectForm(array $overrides = []): array
    {
        return [
            'title' => 'اندازه‌گیری صدای سالن نورد و گزارش',
            'service' => 'noise',
            'province' => 'isfahan',
            'city' => 'isfahan',
            'budget_min' => '۸٬۰۰۰٬۰۰۰',
            'budget_max' => '12000000',
            'description' => self::DESCRIPTION,
            ...$overrides,
        ];
    }

    private function client(): User
    {
        return User::factory()->create(['mobile_verified_at' => now()]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function publishedProject(?User $client = null, array $overrides = []): MarketProject
    {
        $client ??= $this->client();
        $this->actingAs($client)->post(route('market.client.store'), $this->projectForm($overrides))->assertSessionHasNoErrors();
        $project = MarketProject::query()->latest('id')->firstOrFail();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketReviewPage::class)->call('approve', $project->id);
        auth()->logout();

        return $project->refresh();
    }

    private function admin(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
