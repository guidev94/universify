<?php namespace Siamfesp\Banners\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddButtonTargetToBannersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        if (!Schema::hasColumn('siamfesp_banners', 'button_target')) {
            Schema::table('siamfesp_banners', function ($table) {
                $table->string('button_target', 20)->nullable()->default('_self')->after('button_link');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        if (Schema::hasColumn('siamfesp_banners', 'button_target')) {
            Schema::table('siamfesp_banners', function ($table) {
                $table->dropColumn('button_target');
            });
        }
    }
}
