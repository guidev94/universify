<?php namespace Siamfesp\Parcerias\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePartnersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_parcerias_partners')) return;

        Schema::create('siamfesp_parcerias_partners', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('type', 30)->default('siamfesp')->index();
            $table->string('title');
            $table->string('slug');
            $table->string('badge')->nullable();
            $table->string('image', 1024)->nullable();
            $table->text('description')->nullable();
            $table->string('link_url', 1024)->nullable();
            $table->string('link_target', 20)->default('_self');
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->unique(['type', 'slug'], 'siamfesp_parcerias_type_slug_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_parcerias_partners');
    }
}
