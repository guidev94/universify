<?php namespace Siamfesp\Associados\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateAssociateCategoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_associados_categories')) {
            return;
        }

        Schema::create('siamfesp_associados_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('slug', 400)->unique();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_associados_categories');
    }
}
