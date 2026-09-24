<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddRequiresLoginToMenuItems extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_menu_items')) {
            return;
        }

        if (!Schema::hasColumn('siamfesp_menu_items', 'requires_login')) {
            Schema::table('siamfesp_menu_items', function ($table) {
                $table->boolean('requires_login')->default(0)->after('target_blank');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('siamfesp_menu_items') && Schema::hasColumn('siamfesp_menu_items', 'requires_login')) {
            Schema::table('siamfesp_menu_items', function ($table) {
                $table->dropColumn('requires_login');
            });
        }
    }
}
