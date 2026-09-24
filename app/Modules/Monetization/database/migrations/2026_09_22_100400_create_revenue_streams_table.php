<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // کلیدها جدول خودشان را دارند و نه ردیفی در settings: هر کلید علاوه
        // بر روشن/خاموش یک سیاست خاموشی و یک زمان تغییر دارد، و قاعده ۵
        // می‌گوید وضعیت Enum است نه رشته در یک ستون value عمومی.
        Schema::create('revenue_streams', function (Blueprint $table): void {
            $table->id();
            $table->string('stream', 48)->unique();
            $table->boolean('is_enabled');
            $table->string('shutdown_policy', 32)->default('run_to_end');
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_streams');
    }
};
