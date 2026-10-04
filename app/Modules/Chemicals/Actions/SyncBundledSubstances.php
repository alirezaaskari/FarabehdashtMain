<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\Import\BundledSyncResult;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\BundledSubstances;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * افزودن مواد داده اولیه‌ای که هنوز در بانک نیستند، و انتشارشان.
 *
 * تشخیص روی شماره CAS است. ماده موجود **دست نمی‌خورد**: ممکن است مدیر
 * عددی را ویرایش یا حد ایران را اضافه کرده باشد و استقرار بعدی نباید آن را
 * پاک کند. برای همین اجرای دوباره بی‌خطر است و در هر استقرار اجرا می‌شود.
 *
 * ذخیره و انتشار از همان اکشن‌های پنل می‌گذرد تا نسخه، دفتر رویداد و
 * پیوندهای داخلی مثل ویرایش دستی ثبت شوند.
 */
final readonly class SyncBundledSubstances
{
    public function __construct(
        private BundledSubstances $bundle,
        private SaveSubstance $save,
        private PublishSubstance $publish,
    ) {}

    public function handle(): BundledSyncResult
    {
        $existing = array_flip(Substance::query()->pluck('cas_number')->all());

        $created = [];
        $skipped = [];
        $failed = [];

        foreach ($this->bundle->all() as $entry) {
            $cas = (string) ($entry['cas_number'] ?? '');

            if (isset($existing[$cas])) {
                $skipped[] = $cas;

                continue;
            }

            try {
                $substance = $this->save->handle(null, [...$entry, 'slug' => $this->freeSlug((string) ($entry['slug'] ?? ''))], null);
                $this->publish->handle($substance);
                $created[] = $cas;
                $existing[$cas] = true;
            } catch (InvalidArgumentException|RuntimeException $exception) {
                $failed[$cas] = $exception->getMessage();
            }
        }

        return new BundledSyncResult($created, $skipped, $failed);
    }

    /** نشانی پیشنهادی داده، مگر مدیر پیش‌تر آن را برای ماده دیگری برداشته باشد. */
    private function freeSlug(string $slug): string
    {
        $base = Str::slug($slug) ?: 'substance';
        $candidate = $base;
        $suffix = 1;

        while (Substance::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.++$suffix;
        }

        return $candidate;
    }
}
