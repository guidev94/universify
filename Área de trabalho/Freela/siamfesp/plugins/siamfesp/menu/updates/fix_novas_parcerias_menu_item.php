<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;
use Db;

class FixNovasParceriasMenuItem extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_menus') || !Schema::hasTable('siamfesp_menu_items') || !Schema::hasTable('siamfesp_articles_categories')) {
            return;
        }

        $menuId = Db::table('siamfesp_menus')
            ->whereIn('slug', ['novas-parcerias', 'novas-parceirias', 'nossas-parcerias'])
            ->value('id');

        if (!$menuId) {
            $menuId = Db::table('siamfesp_menus')->insertGetId([
                'title' => 'Novas Parcerias',
                'slug' => 'novas-parcerias',
                'description' => '',
                'is_published' => 1,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categoryId = Db::table('siamfesp_articles_categories')
            ->whereIn('slug', ['novas-parcerias', 'novas-parceirias', 'nossas-parcerias'])
            ->value('id');

        if (!$categoryId) {
            return;
        }

        $existingItemId = Db::table('siamfesp_menu_items')
            ->where('menu_id', $menuId)
            ->where('slug', 'parceiros')
            ->value('id');

        $payload = [
            'menu_id' => $menuId,
            'parent_id' => null,
            'title' => 'Parceiros',
            'slug' => 'parceiros',
            'type' => 'category',
            'page_name' => 'revista',
            'page_param' => 'slug',
            'article_id' => null,
            'category_id' => $categoryId,
            'dynamic_source' => null,
            'dynamic_record_id' => null,
            'url' => null,
            'target_blank' => 0,
            'requires_login' => 0,
            'is_published' => 1,
            'sort_order' => 1,
            'updated_at' => now(),
        ];

        if ($existingItemId) {
            Db::table('siamfesp_menu_items')
                ->where('id', $existingItemId)
                ->update($payload);
            return;
        }

        $payload['created_at'] = now();
        Db::table('siamfesp_menu_items')->insert($payload);
    }

    public function down()
    {
        // Mantém os dados, pois podem ter sido ajustados manualmente no administrador.
    }
}
