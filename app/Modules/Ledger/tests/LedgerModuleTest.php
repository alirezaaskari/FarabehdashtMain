<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class LedgerModuleTest extends TestCase
{
    public function test_module_is_enabled(): void
    {
        $this->assertTrue(
            $this->app->make(ModuleRegistry::class)->isEnabled('Ledger'),
        );
    }
}
