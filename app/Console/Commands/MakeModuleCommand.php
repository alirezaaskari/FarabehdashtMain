<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * اسکلت یک ماژول تازه را طبق ساختار استاندارد می‌سازد.
 *
 * ساخته‌شدن ماژول، آن را فعال نمی‌کند؛ فعال‌سازی یک خط دستی در
 * config/modules.php است تا هیچ ماژولی ناخواسته وارد برنامه نشود.
 */
final class MakeModuleCommand extends Command
{
    protected $signature = 'fbh:make-module {name : نام ماژول به PascalCase، مثل Encyclopedia}';

    protected $description = 'ساخت اسکلت یک ماژول تازه فرابهداشت';

    /** پوشه‌هایی که هر ماژول از روز اول دارد. */
    private const DIRECTORIES = [
        'Domain',
        'Actions',
        'Http/Controllers',
        'Providers',
        'routes',
        'database/migrations',
        'resources/views',
        'tests',
    ];

    public function handle(Filesystem $files, ModuleRegistry $registry): int
    {
        $module = str($this->argument('name'))->studly()->toString();

        if ($module === '') {
            $this->components->error('نام ماژول نمی‌تواند خالی باشد.');

            return self::FAILURE;
        }

        $root = $registry->path($module);

        if ($files->isDirectory($root)) {
            $this->components->error("ماژول «{$module}» از قبل وجود دارد: {$root}");

            return self::FAILURE;
        }

        foreach (self::DIRECTORIES as $directory) {
            $files->ensureDirectoryExists($root.'/'.$directory);
        }

        $key = str($module)->snake()->toString();

        $this->writeStub($files, 'README.md.stub', $root.'/README.md', $module, $key);
        $this->writeStub($files, 'Provider.php.stub', $root."/Providers/{$module}ServiceProvider.php", $module, $key);
        $this->writeStub($files, 'routes-web.php.stub', $root.'/routes/web.php', $module, $key);
        $this->writeStub($files, 'test.php.stub', $root."/tests/{$module}ModuleTest.php", $module, $key);

        $this->components->info("ماژول «{$module}» ساخته شد.");
        $this->components->bulletList([
            "مسیر: {$root}",
            "برای فعال‌سازی، '{$module}' را به فهرست enabled در config/modules.php اضافه کنید.",
            'سپس README ماژول را پر کنید.',
        ]);

        return self::SUCCESS;
    }

    private function writeStub(
        Filesystem $files,
        string $stub,
        string $target,
        string $module,
        string $key,
    ): void {
        $contents = str_replace(
            ['{{ module }}', '{{ moduleKey }}'],
            [$module, $key],
            $files->get(base_path('stubs/fbh-module/'.$stub)),
        );

        $files->put($target, $contents);
    }
}
