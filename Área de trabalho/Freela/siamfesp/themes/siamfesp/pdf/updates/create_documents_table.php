<?php namespace Siamfesp\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateDocumentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_estudos_documents')) {
            return;
        }

        Schema::create('siamfesp_estudos_documents', function($table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->string('title');
            $table->unsignedBigInteger('theme_id')->nullable();

            // Ex: 01/2018 (MM/YYYY)
            $table->string('year_month', 7)->nullable();

            // Caso queira armazenar um link externo
            $table->string('url')->nullable();

            // Caminhos/URLs para uso com Media Library
            $table->string('pdf', 1024)->nullable();
            $table->string('cover', 1024)->nullable();

            $table->timestamps();

            $table->index('theme_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('siamfesp_estudos_documents');
    }
}
