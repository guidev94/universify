<?php namespace Siamfesp\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddPdfCoverColumns extends Migration
{
    public function up()
    {
        // MySQL 5.7 não suporta "ADD COLUMN IF NOT EXISTS".
        // Por isso verificamos a existência antes de adicionar.
        Schema::table('siamfesp_estudos_documents', function($table) {
            if (!Schema::hasColumn('siamfesp_estudos_documents', 'pdf')) {
                $table->string('pdf', 1024)->nullable();
            }
            if (!Schema::hasColumn('siamfesp_estudos_documents', 'cover')) {
                $table->string('cover', 1024)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('siamfesp_estudos_documents', function($table) {
            if (Schema::hasColumn('siamfesp_estudos_documents', 'pdf')) {
                $table->dropColumn('pdf');
            }
            if (Schema::hasColumn('siamfesp_estudos_documents', 'cover')) {
                $table->dropColumn('cover');
            }
        });
    }
}
