<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Db;
use Schema;

class ConvertDownloadMenuItemToPage extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_menu_items')) {
            return;
        }

        Db::table('siamfesp_menu_items')
            ->where('slug', 'download-gratuito')
            ->update([
                'type' => 'page',
                'page_name' => 'downloads',
                'page_param' => 'slug',
                'category_id' => null,
                'article_id' => null,
                'dynamic_source' => null,
                'dynamic_record_id' => null,
                'url' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_menu_items')) {
            return;
        }

        Db::table('siamfesp_menu_items')
            ->where('slug', 'download-gratuito')
            ->where('page_name', 'downloads')
            ->update([
                'type' => 'category',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
