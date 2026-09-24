<?php namespace Siamfesp\Banners\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateInternalBannersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_internal_banners')) {
            return;
        }

        Schema::create('siamfesp_internal_banners', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedInteger('menu_item_id')->nullable()->index();
            $table->string('name');
            $table->string('eyebrow', 120)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image', 1000)->nullable();
            $table->boolean('show_overlay')->default(true)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_internal_banners');
    }
}
