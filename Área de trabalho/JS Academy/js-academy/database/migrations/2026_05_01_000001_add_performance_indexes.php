<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'progress_user_status_idx');
            $table->index(['user_id', 'lesson_id'], 'progress_user_lesson_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('xp', 'users_xp_idx');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['track_id', 'order'], 'lessons_track_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            $table->dropIndex('progress_user_status_idx');
            $table->dropIndex('progress_user_lesson_idx');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_xp_idx');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_track_order_idx');
        });
    }
};
