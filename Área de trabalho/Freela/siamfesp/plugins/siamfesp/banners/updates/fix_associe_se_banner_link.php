<?php namespace Siamfesp\Banners\Updates;

use DB;
use Schema;
use October\Rain\Database\Updates\Migration;

class FixAssocieSeBannerLink extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_banners')) {
            return;
        }

        DB::table('siamfesp_banners')
            ->where(function ($query) {
                $query->where('title_1', 'like', '%ASSOCIE%')
                    ->orWhere('title_1', 'like', '%Associe%')
                    ->orWhere('title_1', 'like', '%associe%')
                    ->orWhere('title_2', 'like', '%ASSOCIE%')
                    ->orWhere('title_2', 'like', '%Associe%')
                    ->orWhere('title_2', 'like', '%associe%');
            })
            ->where(function ($query) {
                $query->whereNull('button_link')
                    ->orWhere('button_link', '')
                    ->orWhere('button_link', '#')
                    ->orWhere('button_link', '/contato')
                    ->orWhere('button_link', 'contato');
            })
            ->update([
                'button_link'   => '/associe-se',
                'button_target' => '_self',
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        // Não desfazemos esse ajuste para não voltar a enviar o visitante para Contato.
    }
}
