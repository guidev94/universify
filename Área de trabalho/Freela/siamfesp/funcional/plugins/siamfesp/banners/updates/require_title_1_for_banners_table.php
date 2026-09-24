<?php namespace Siamfesp\Banners\Updates;

use DB;
use Schema;
use October\Rain\Database\Updates\Migration;

class RequireTitle1ForBannersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        // Garante que banners antigos não fiquem em branco na listagem/reordenação.
        DB::statement("UPDATE `siamfesp_banners` SET `title_1` = CONCAT('Banner #', `id`) WHERE `title_1` IS NULL OR TRIM(`title_1`) = ''");
        DB::statement("ALTER TABLE `siamfesp_banners` MODIFY `title_1` VARCHAR(255) NOT NULL");
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        DB::statement("ALTER TABLE `siamfesp_banners` MODIFY `title_1` VARCHAR(255) NULL");
    }
}
