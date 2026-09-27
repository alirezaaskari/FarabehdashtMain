<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // صفحه شرکت کارفرما (بخش ۲۰-۱، DEC-65). مثل صفحه مشاور: ستون‌های نمایشی
        // نسخه منتشرشده‌اند و ویرایش تازه در `pending` تا تأیید مدیر می‌ماند.
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('slug', 60)->nullable()->unique();

            $table->string('name', 120)->nullable();
            $table->string('industry', 80)->nullable();
            $table->string('size', 16)->nullable();
            $table->string('province', 40)->nullable();
            $table->string('city', 40)->nullable();
            $table->text('about')->nullable();
            $table->unsignedBigInteger('logo_id')->nullable();
            $table->timestamp('published_at')->nullable();
            // با غیرفعال‌شدن نقش کارفرما، صفحه و آگهی‌ها بی‌آنکه پاک شوند پنهان می‌شوند.
            $table->timestamp('hidden_at')->nullable();

            $table->json('pending')->nullable();
            $table->string('status', 16);
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });

        Schema::create('company_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });

        // آگهی شغلی. «زنده» یعنی منتشرشده، پیش از expires_at و بسته‌نشده؛ هر
        // ویرایش آگهی منتشرشده در `pending` تا تأیید مدیر می‌ماند.
        Schema::create('job_postings', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('title', 120)->nullable();
            $table->string('province', 40)->nullable();
            $table->string('city', 40)->nullable();
            $table->string('employment_type', 16)->nullable();
            $table->unsignedTinyInteger('min_experience_years')->nullable();
            // DEC-67: حقوق اختیاری است؛ خالی یعنی «توافقی».
            $table->unsignedBigInteger('salary_min_toman')->nullable();
            $table->unsignedBigInteger('salary_max_toman')->nullable();
            $table->text('description')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('pending')->nullable();
            $table->string('status', 16);
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'expires_at', 'closed_at']);
            $table->index(['city', 'expires_at']);
            $table->index(['status', 'submitted_at']);
        });

        // هر دوره انتشار یک ردیف: پولی یا «اولین آگهی رایگان» (DEC-63). قیمت و
        // مدت همان لحظه ثبت می‌شود تا تغییر تنظیم مدیر روی گذشته اثر نکند.
        Schema::create('job_posting_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->unsignedBigInteger('price_toman');
            $table->unsignedSmallInteger('days');
            $table->boolean('is_free')->default(false);
            $table->string('status', 16);
            $table->string('payment_source', 16)->nullable();
            $table->string('gateway_authority', 64)->nullable()->index();
            $table->string('gateway_ref_id', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['posting_id', 'status']);
        });

        $this->seedSkills();
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posting_payments');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('companies');
    }

    /**
     * فهرست آغازین مهارت‌ها (DEC-71) در دسته‌بندی Core؛ برچسبی که مدیر پیش‌تر
     * ساخته دست نمی‌خورد.
     */
    private function seedSkills(): void
    {
        if (! Schema::hasTable('taxonomy_terms')) {
            return;
        }

        $now = Carbon::now();
        $position = 0;
        $rows = [];

        foreach ((array) config('jobs.skills', []) as $slug => $name) {
            $rows[] = [
                'taxonomy' => 'job_skill',
                'slug' => $slug,
                'name' => $name,
                'position' => $position++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('taxonomy_terms')->insertOrIgnore($rows);
    }
};
