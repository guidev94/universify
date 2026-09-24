<?php namespace Siamfesp\Banners\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddImageOnlyToBannersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        if (!Schema::hasColumn('siamfesp_banners', 'is_image_only')) {
            Schema::table('siamfesp_banners', function ($table) {
                $table->boolean('is_image_only')->default(false)->index()->after('image');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        if (Schema::hasColumn('siamfesp_banners', 'is_image_only')) {
            Schema::table('siamfesp_banners', function ($table) {
                $table->dropColumn('is_image_only');
            });
        }
    }
}
