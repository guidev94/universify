<?php namespace Siamfesp\Pdf\Updates;

use Db;
use Schema;
use October\Rain\Database\Updates\Migration;

class ResizeDocumentFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_estudos_documents')) {
            return;
        }

        if (Schema::hasColumn('siamfesp_estudos_documents', 'year_month')) {
            Db::statement('ALTER TABLE `siamfesp_estudos_documents` MODIFY `year_month` VARCHAR(20) NULL');
        }

        foreach (['url', 'pdf', 'cover'] as $column) {
            if (Schema::hasColumn('siamfesp_estudos_documents', $column)) {
                Db::statement("ALTER TABLE `siamfesp_estudos_documents` MODIFY `{$column}` VARCHAR(1024) NULL");
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_estudos_documents')) {
            return;
        }

        if (Schema::hasColumn('siamfesp_estudos_documents', 'year_month')) {
            Db::statement('ALTER TABLE `siamfesp_estudos_documents` MODIFY `year_month` VARCHAR(7) NULL');
        }
    }
}
