<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نمای میزکار در دیتابیس است نه نشست (DEC-21): روی گوشی و رایانه یکسان
        // می‌ماند. `view` یا «personal» است یا مقدار نوع یک پروفایل.
        Schema::create('workspace_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('view', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_preferences');
    }
};
