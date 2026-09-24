<?php namespace Siamfesp\Artigos\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddArticleImageColumns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_articles')) {
            return;
        }

        Schema::table('siamfesp_articles', function ($table) {
            if (!Schema::hasColumn('siamfesp_articles', 'image_intro_path')) {
                $table->string('image_intro_path')->nullable()->after('images_json');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'image_fulltext_path')) {
                $table->string('image_fulltext_path')->nullable()->after('image_intro_path');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'image_intro_alt')) {
                $table->string('image_intro_alt')->nullable()->after('image_fulltext_path');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'image_intro_caption')) {
                $table->string('image_intro_caption')->nullable()->after('image_intro_alt');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'image_fulltext_alt')) {
                $table->string('image_fulltext_alt')->nullable()->after('image_intro_caption');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'image_fulltext_caption')) {
                $table->string('image_fulltext_caption')->nullable()->after('image_fulltext_alt');
            }
            if (!Schema::hasColumn('siamfesp_articles', 'legacy_images_json')) {
                $table->longText('legacy_images_json')->nullable()->after('urls_json');
            }
        });
    }

    public function down()
    {
        // Não remove colunas para não perder imagens cadastradas/migradas.
    }
}
