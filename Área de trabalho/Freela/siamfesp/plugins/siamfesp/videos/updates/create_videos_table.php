<?php namespace Siamfesp\Videos\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateVideosTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_videos_videos')) return;

        Schema::create('siamfesp_videos_videos', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('source_type', 20)->default('youtube')->index();
            $table->string('youtube_url', 1024)->nullable();
            $table->string('video_file', 1024)->nullable();
            $table->string('thumbnail', 1024)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->dateTime('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_videos_videos');
    }
}
