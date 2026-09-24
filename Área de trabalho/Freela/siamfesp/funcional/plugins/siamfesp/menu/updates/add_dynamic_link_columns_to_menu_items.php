<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddDynamicLinkColumnsToMenuItems extends Migration
{
    public function up()
    {
        Schema::table('siamfesp_menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('siamfesp_menu_items', 'dynamic_source')) {
                $table->string('dynamic_source', 120)->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('siamfesp_menu_items', 'dynamic_record_id')) {
                $table->integer('dynamic_record_id')->unsigned()->nullable()->after('dynamic_source');
                $table->index('dynamic_record_id', 'siamfesp_menu_items_dynamic_record_id_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('siamfesp_menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('siamfesp_menu_items', 'dynamic_record_id')) {
                try {
                    $table->dropIndex('siamfesp_menu_items_dynamic_record_id_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('dynamic_record_id');
            }

            if (Schema::hasColumn('siamfesp_menu_items', 'dynamic_source')) {
                $table->dropColumn('dynamic_source');
            }
        });
    }
}
