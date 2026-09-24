<?php namespace Siamfesp\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateThemesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_estudos_themes')) {
            return;
        }

        Schema::create('siamfesp_estudos_themes', function($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_estudos_themes');
    }
}
