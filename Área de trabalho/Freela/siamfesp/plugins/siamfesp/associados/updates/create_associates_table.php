<?php namespace Siamfesp\Associados\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateAssociatesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_associados')) {
            return;
        }

        Schema::create('siamfesp_associados', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->string('slug', 400)->unique();
            $table->string('image', 1000)->nullable();
            $table->string('image_alt')->nullable();
            $table->string('associate_link', 1500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->index('category_id');
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_associados');
    }
}
