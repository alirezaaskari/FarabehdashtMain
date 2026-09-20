<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Exception\DuplicateFormula;
use Farabehdasht\CalcEngine\Exception\UnknownFormula;

/**
 * فهرست فرمول‌های شناخته‌شده، گروه‌بندی‌شده بر اساس شناسه و نسخه.
 *
 * ثبت دوباره یک نسخه خطا است. این تنها جایی است که قاعده «رفتار فرمول بدون
 * نسخه تازه عوض نمی‌شود» در زمان اجرا نگهبان دارد؛ نگهبان دوم تست قفل است.
 */
final class FormulaRegistry
{
    /** @var array<string, array<string, Formula>> */
    private array $formulas = [];

    /**
     * @param  iterable<Formula>  $formulas
     */
    public function __construct(iterable $formulas = [])
    {
        foreach ($formulas as $formula) {
            $this->register($formula);
        }
    }

    /**
     * @throws DuplicateFormula
     */
    public function register(Formula $formula): void
    {
        $id = $formula->id();
        $version = $formula->version();

        if (isset($this->formulas[$id][$version])) {
            throw DuplicateFormula::of($id, $version);
        }

        $this->formulas[$id][$version] = $formula;
    }

    public function has(string $id, ?string $version = null): bool
    {
        if (! isset($this->formulas[$id])) {
            return false;
        }

        return $version === null || isset($this->formulas[$id][$version]);
    }

    /**
     * نسخه مشخص، یا آخرین نسخه اگر نسخه‌ای خواسته نشده باشد.
     *
     * @throws UnknownFormula
     */
    public function get(string $id, ?string $version = null): Formula
    {
        if (! isset($this->formulas[$id])) {
            throw UnknownFormula::id($id);
        }

        if ($version === null) {
            return $this->latest($id);
        }

        return $this->formulas[$id][$version] ?? throw UnknownFormula::version($id, $version);
    }

    /**
     * @throws UnknownFormula
     */
    public function latest(string $id): Formula
    {
        $versions = $this->versionsOf($id);

        return $this->formulas[$id][$versions[count($versions) - 1]];
    }

    /**
     * نسخه‌ها از قدیم به جدید.
     *
     * @return list<string>
     *
     * @throws UnknownFormula
     */
    public function versionsOf(string $id): array
    {
        if (! isset($this->formulas[$id])) {
            throw UnknownFormula::id($id);
        }

        $versions = array_keys($this->formulas[$id]);
        usort($versions, version_compare(...));

        return $versions;
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->formulas);
    }

    /**
     * همه نسخه‌های همه فرمول‌ها.
     *
     * @return list<Formula>
     */
    public function all(): array
    {
        $all = [];

        foreach ($this->formulas as $versions) {
            foreach ($versions as $formula) {
                $all[] = $formula;
            }
        }

        return $all;
    }
}
