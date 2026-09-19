<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // ورود با موبایل است؛ ایمیل و رمز عبور اختیاری و ثانویه‌اند.
            $table->string('mobile', 11)->unique()->after('id');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');

            // کد ملی رمزنگاری‌شده ذخیره می‌شود، پس روی خودش ایندکس یکتا ممکن نیست.
            // برای تشخیص تکراری، هش جداگانه نگه می‌داریم.
            $table->text('national_code')->nullable()->after('mobile_verified_at');
            $table->string('national_code_hash', 64)->nullable()->unique()->after('national_code');

            $table->string('status', 20)->default(UserStatus::Active->value)->after('national_code_hash');
            $table->timestamp('onboarded_at')->nullable()->after('status');

            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            // حساب با شماره موبایل ساخته می‌شود؛ نام در مرحله تکمیل پروفایل
            // پر می‌شود و ایمیل و رمز عبور اصلاً اجباری نیستند.
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['mobile']);
            $table->dropUnique(['national_code_hash']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'mobile',
                'mobile_verified_at',
                'national_code',
                'national_code_hash',
                'status',
                'onboarded_at',
            ]);
        });
    }
};
