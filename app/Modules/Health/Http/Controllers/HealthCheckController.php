<?php

declare(strict_types=1);

namespace App\Modules\Health\Http\Controllers;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;

final readonly class HealthCheckController
{
    public function __construct(private ModuleRegistry $modules) {}

    public function __invoke(Application $app): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'app' => config('app.name'),
            'laravel' => $app->version(),
            'php' => PHP_VERSION,
            'modules' => $this->modules->enabled(),
        ]);
    }
}
