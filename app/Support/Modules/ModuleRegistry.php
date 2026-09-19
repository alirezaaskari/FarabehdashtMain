<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * تنها مرجع شناخت ماژول‌ها.
 *
 * هیچ جای دیگری از برنامه نباید مستقیم config('modules') را بخواند؛
 * همه از اینجا می‌پرسند تا اگر روزی منبع فعال‌بودن ماژول‌ها عوض شد
 * (مثلاً به جدول تنظیمات منتقل شد) فقط همین کلاس تغییر کند.
 */
final readonly class ModuleRegistry
{
    public function __construct(private Config $config) {}

    /**
     * نام ماژول‌های فعال، به ترتیب ثبت.
     *
     * @return list<string>
     */
    public function enabled(): array
    {
        /** @var list<string> $modules */
        $modules = $this->config->get('modules.enabled', []);

        return array_values($modules);
    }

    public function isEnabled(string $module): bool
    {
        return in_array($module, $this->enabled(), strict: true);
    }

    /**
     * کلاس ServiceProvider یک ماژول، طبق قرارداد نام‌گذاری.
     *
     * @return class-string
     */
    public function providerClass(string $module): string
    {
        /** @var class-string $class */
        $class = sprintf(
            '%s\\%s\\Providers\\%sServiceProvider',
            $this->rootNamespace(),
            $module,
            $module,
        );

        return $class;
    }

    /**
     * مسیر مطلق یک ماژول، با زیرمسیر اختیاری.
     */
    public function path(string $module, string $sub = ''): string
    {
        $path = base_path($this->rootPath().'/'.$module);

        return $sub === '' ? $path : $path.'/'.ltrim($sub, '/');
    }

    public function rootPath(): string
    {
        /** @var string $path */
        $path = $this->config->get('modules.path', 'app/Modules');

        return trim($path, '/');
    }

    public function rootNamespace(): string
    {
        /** @var string $namespace */
        $namespace = $this->config->get('modules.namespace', 'App\\Modules');

        return trim($namespace, '\\');
    }
}
