<?php namespace Siamfesp\Parcerias\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Siamfesp\Parcerias\Models\Partner;

class MigrateLocalPartnerImagesToUpload extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_parcerias_partners')) {
            return;
        }

        Partner::whereNotNull('image')
            ->where('image', '<>', '')
            ->chunk(50, function ($partners) {
                foreach ($partners as $partner) {
                    // Migra somente caminhos locais do site. URLs externas permanecem no campo antigo.
                    $partner->migrateLocalImageToUpload(true);
                }
            });
    }

    public function down()
    {
        // Não desfaz para não remover arquivos que já foram anexados no CMS.
    }
}
