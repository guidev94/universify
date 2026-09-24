<?php namespace Siamfesp\Menu\Updates;

use October\Rain\Database\Updates\Migration;
use DB;
use Schema;

class EnsureFooterMenu extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('siamfesp_menus') || !Schema::hasTable('siamfesp_menu_items')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $menu = DB::table('siamfesp_menus')->where('slug', 'rodape')->first();

        if ($menu) {
            $menuId = $menu->id;
        } else {
            $menuId = DB::table('siamfesp_menus')->insertGetId($this->menuRow([
                'title' => 'Rodapé',
                'slug' => 'rodape',
                'description' => 'Menu administrável exibido no rodapé do site.',
                'is_published' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Se o cliente já cadastrou itens no Rodapé, não sobrescreve.
        $hasItems = DB::table('siamfesp_menu_items')->where('menu_id', $menuId)->exists();
        if ($hasItems) {
            return;
        }

        foreach ($this->defaultColumns() as $columnIndex => $column) {
            $parentId = DB::table('siamfesp_menu_items')->insertGetId($this->itemRow([
                'menu_id' => $menuId,
                'parent_id' => null,
                'title' => $column['title'],
                'slug' => $column['slug'],
                'type' => 'url',
                'url' => '#',
                'target_blank' => 0,
                'requires_login' => 0,
                'is_published' => 1,
                'sort_order' => ($columnIndex + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            foreach ($column['items'] as $itemIndex => $item) {
                DB::table('siamfesp_menu_items')->insert($this->itemRow([
                    'menu_id' => $menuId,
                    'parent_id' => $parentId,
                    'title' => $item['title'],
                    'slug' => $item['slug'],
                    'type' => 'url',
                    'url' => $item['url'],
                    'target_blank' => 0,
                    'requires_login' => 0,
                    'is_published' => 1,
                    'sort_order' => ($itemIndex + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('siamfesp_menus') || !Schema::hasTable('siamfesp_menu_items')) {
            return;
        }

        $menu = DB::table('siamfesp_menus')->where('slug', 'rodape')->first();
        if (!$menu) {
            return;
        }

        DB::table('siamfesp_menu_items')->where('menu_id', $menu->id)->delete();
        DB::table('siamfesp_menus')->where('id', $menu->id)->delete();
    }

    protected function defaultColumns(): array
    {
        return [
            [
                'title' => 'Sobre o Siamfesp',
                'slug' => 'sobre-o-siamfesp',
                'items' => [
                    ['title' => 'Quem Somos', 'slug' => 'quem-somos', 'url' => '/sobre-o-siamfesp/quem-somos'],
                    ['title' => 'Histórico de Gestões', 'slug' => 'historico-de-gestoes', 'url' => '/sobre-o-siamfesp/historico-de-gestoes'],
                    ['title' => 'Diretoria', 'slug' => 'diretoria', 'url' => '/sobre-o-siamfesp/diretoria'],
                    ['title' => 'Instalações', 'slug' => 'instalacoes', 'url' => '/sobre-o-siamfesp/instalacoes'],
                    ['title' => 'Indicadores Econômicos', 'slug' => 'indicadores-economicos', 'url' => '/servicos/pdf/dados-estatisticos'],
                    ['title' => 'Links Úteis', 'slug' => 'links-uteis', 'url' => '/sobre-o-siamfesp/links-uteis'],
                ],
            ],
            [
                'title' => 'Associados',
                'slug' => 'associados',
                'items' => [
                    ['title' => 'Artefatos Metálicos', 'slug' => 'artefatos-metalicos', 'url' => '/associados/artefatos-metalicos'],
                    ['title' => 'Esquadrias', 'slug' => 'esquadrias', 'url' => '/associados/esquadrias'],
                    ['title' => 'Fechaduras', 'slug' => 'fechaduras', 'url' => '/associados/fechaduras'],
                    ['title' => 'Metais Sanitários', 'slug' => 'metais-sanitarios', 'url' => '/associados/metais-sanitarios'],
                    ['title' => 'Utilidades Domésticas', 'slug' => 'utilidades-domesticas', 'url' => '/associados/utilidades-domesticas'],
                ],
            ],
            [
                'title' => 'Serviços',
                'slug' => 'servicos',
                'items' => [
                    ['title' => 'Dados Estatísticos', 'slug' => 'dados-estatisticos', 'url' => '/servicos/pdf/dados-estatisticos'],
                    ['title' => 'Trabalhista', 'slug' => 'trabalhista', 'url' => '/servicos/pdf/trabalhista'],
                    ['title' => 'Tributário', 'slug' => 'tributario', 'url' => '/servicos/pdf/tributario'],
                    ['title' => 'Acervo Digital', 'slug' => 'acervo-digital', 'url' => '/servicos/pdf/acervo-digital'],
                ],
            ],
            [
                'title' => 'Qualidade',
                'slug' => 'qualidade',
                'items' => [
                    ['title' => 'ABNT/Comissões', 'slug' => 'abnt-comissoes', 'url' => '/qualidade-e-normalizacao#abnt-comissoes'],
                    ['title' => 'Inmetro', 'slug' => 'inmetro', 'url' => '/qualidade-e-normalizacao#inmetro'],
                    ['title' => 'Normas Técnicas', 'slug' => 'normas-tecnicas', 'url' => '/qualidade-e-normalizacao#normas-tecnicas'],
                    ['title' => 'PBQP-H/Qualihab', 'slug' => 'pbqp-h-qualihab', 'url' => '/qualidade-e-normalizacao#pbqp-h-qualihab'],
                ],
            ],
            [
                'title' => 'Imprensa',
                'slug' => 'imprensa',
                'items' => [
                    ['title' => 'Em ação', 'slug' => 'em-acao', 'url' => '/imprensa#siamfesp-em-acao'],
                    ['title' => 'Legislação', 'slug' => 'legislacao', 'url' => '/imprensa#legislacao'],
                    ['title' => 'Normalização', 'slug' => 'normalizacao', 'url' => '/imprensa#normalizacao'],
                    ['title' => 'Jornal', 'slug' => 'jornal', 'url' => '/imprensa#jornal'],
                    ['title' => 'Revistas', 'slug' => 'revistas', 'url' => '/imprensa#revistas'],
                    ['title' => 'Eventos', 'slug' => 'eventos', 'url' => '/#eventos'],
                    ['title' => 'Agenda', 'slug' => 'agenda', 'url' => '/agenda'],
                ],
            ],
        ];
    }

    protected function menuRow(array $row): array
    {
        return $this->onlyExistingColumns('siamfesp_menus', $row);
    }

    protected function itemRow(array $row): array
    {
        return $this->onlyExistingColumns('siamfesp_menu_items', $row);
    }

    protected function onlyExistingColumns(string $table, array $row): array
    {
        $filtered = [];

        foreach ($row as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}
