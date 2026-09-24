<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateMenuItemsTable extends Migration
{
    public function up()
    {
        Schema::create('siamfesp_menu_items', function ($table) {
            $table->increments('id');
            $table->integer('menu_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('type', 20)->default('page');
            $table->string('page_name')->nullable();
            $table->string('page_param')->default('slug');
            $table->integer('article_id')->unsigned()->nullable();
            $table->integer('category_id')->unsigned()->nullable();
            $table->string('url', 500)->nullable();
            $table->string('css_class')->nullable();
            $table->boolean('target_blank')->default(0);
            $table->boolean('is_published')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('menu_id');
            $table->index('parent_id');
            $table->index('article_id');
            $table->index('category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_menu_items');
    }
}
