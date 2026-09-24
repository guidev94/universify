<?php namespace Siamfesp\Artigos\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateArticleCategoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_articles_categories')) {
            return;
        }

        Schema::create('siamfesp_articles_categories', function($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('legacy_id')->nullable()->unique();
            $table->unsignedInteger('legacy_parent_id')->nullable();
            $table->integer('legacy_lft')->nullable();
            $table->integer('legacy_rgt')->nullable();
            $table->integer('legacy_level')->nullable();
            $table->string('legacy_path', 400)->nullable();
            $table->string('legacy_extension', 50)->nullable();
            $table->string('title');
            $table->string('slug', 400)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->string('legacy_note')->nullable();
            $table->text('legacy_params')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('metadata_json')->nullable();
            $table->unsignedInteger('legacy_access')->nullable();
            $table->unsignedInteger('legacy_hits')->nullable();
            $table->string('legacy_language', 7)->nullable();
            $table->unsignedInteger('legacy_version')->nullable();
            $table->timestamps();
            $table->index('parent_id');
            $table->index('legacy_parent_id');
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_articles_categories');
    }
}
