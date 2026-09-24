<?php namespace Siamfesp\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddSortOrderColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_estudos_documents')) {
            Schema::table('siamfesp_estudos_documents', function ($table) {
                if (!Schema::hasColumn('siamfesp_estudos_documents', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->index();
                }
            });
        }

        if (Schema::hasTable('siamfesp_estudos_themes')) {
            Schema::table('siamfesp_estudos_themes', function ($table) {
                if (!Schema::hasColumn('siamfesp_estudos_themes', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->index();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('siamfesp_estudos_documents')) {
            Schema::table('siamfesp_estudos_documents', function ($table) {
                if (Schema::hasColumn('siamfesp_estudos_documents', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }

        if (Schema::hasTable('siamfesp_estudos_themes')) {
            Schema::table('siamfesp_estudos_themes', function ($table) {
                if (Schema::hasColumn('siamfesp_estudos_themes', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }
    }
}
