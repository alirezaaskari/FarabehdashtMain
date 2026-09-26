<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Tests;

use App\Models\User;
use App\Modules\Bundles\Actions\ChangeBundleStatus;
use App\Modules\Bundles\Actions\SaveBundle;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Illuminate\Support\Str;

/**
 * بسته نمونه: فایل ۹۰ هزار + دوره ۱۵۰ هزار + سه ماه حرفه‌ای (۸۷۰ هزار) = ۱٬۱۱۰٬۰۰۰؛ قیمت بسته ۹۹۹٬۰۰۰.
 */
trait BundleFixtures
{
    private Product $product;

    private Course $course;

    private function bundle(bool $publish = true, string $price = '999000'): Bundle
    {
        $this->product = Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'noise-form-'.Str::random(4),
            'title' => 'فرم اندازه‌گیری صدا',
            'description' => 'فرم آماده ثبت اندازه‌گیری تراز فشار صوت.',
            'price_toman' => 90_000,
            'status' => ProductStatus::Published,
        ]);

        $this->course = Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'noise-course-'.Str::random(4),
            'title' => 'دوره ارزیابی صدا',
            'description' => 'اندازه‌گیری و ارزیابی صدا در محیط کار.',
            'price_toman' => 150_000,
            'status' => CourseStatus::Published,
        ]);

        $admin = User::factory()->create();

        $bundle = $this->app->make(SaveBundle::class)->handle([
            'title' => 'بسته ارزیابی صدای کارخانه',
            'slug' => 'noise-kit',
            'description' => 'هرچه برای ارزیابی صدای یک کارخانه لازم است.',
            'price' => $price,
            'items' => ['product:'.$this->product->id, 'course:'.$this->course->id, 'pro:3'],
        ], $admin->id);

        if ($publish) {
            $this->app->make(ChangeBundleStatus::class)->publish($bundle, $admin->id);
        }

        return $bundle->refresh();
    }
}
