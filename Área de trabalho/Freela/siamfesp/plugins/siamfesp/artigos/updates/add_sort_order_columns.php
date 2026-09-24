<?php namespace Siamfesp\Artigos\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddSortOrderColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('siamfesp_articles')) {
            Schema::table('siamfesp_articles', function ($table) {
                if (!Schema::hasColumn('siamfesp_articles', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->index();
                }
            });
        }

        if (Schema::hasTable('siamfesp_articles_categories')) {
            Schema::table('siamfesp_articles_categories', function ($table) {
                if (!Schema::hasColumn('siamfesp_articles_categories', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->index();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('siamfesp_articles')) {
            Schema::table('siamfesp_articles', function ($table) {
                if (Schema::hasColumn('siamfesp_articles', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }

        if (Schema::hasTable('siamfesp_articles_categories')) {
            Schema::table('siamfesp_articles_categories', function ($table) {
                if (Schema::hasColumn('siamfesp_articles_categories', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }
    }
}
