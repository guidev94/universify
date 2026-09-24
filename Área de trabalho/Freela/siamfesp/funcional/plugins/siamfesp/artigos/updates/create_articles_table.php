<?php namespace Siamfesp\Artigos\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateArticlesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_articles')) {
            return;
        }

        Schema::create('siamfesp_articles', function($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedInteger('joomla_id')->nullable()->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('alias', 400)->nullable();
            $table->mediumText('introtext')->nullable();
            $table->longText('fulltext')->nullable();
            $table->tinyInteger('state')->default(1);
            $table->tinyInteger('featured')->default(0);
            $table->unsignedInteger('access')->default(1);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('publish_up')->nullable();
            $table->timestamp('publish_down')->nullable();
            $table->string('language', 7)->nullable()->default('*');
            $table->text('metakey')->nullable();
            $table->text('metadesc')->nullable();
            $table->longText('images_json')->nullable();
            $table->longText('urls_json')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->index('category_id');
            $table->index('alias');
            $table->index('publish_up');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_articles');
    }
}
