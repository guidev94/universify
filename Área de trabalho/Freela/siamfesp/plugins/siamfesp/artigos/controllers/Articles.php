<?php namespace Siamfesp\Artigos\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Articles extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Artigos', 'artigos', 'articles');
    }

    /**
     * Prioriza resultados mais relevantes na busca do admin.
     *
     * A palavra digitada no campo de busca é usada dinamicamente.
     * Ex.: se buscar "siamfesp", títulos que começam com siamfesp vêm primeiro.
     * Ex.: se buscar "feicon", títulos que começam com feicon vêm primeiro.
     */
    public function listExtendQuery($query)
    {
        $search = $this->getAdminSearchTerm();

        if ($search === '') {
            return;
        }

        $term = mb_strtolower(trim((string) $search), 'UTF-8');

        if ($term === '') {
            return;
        }

        $termSql = str_replace("'", "''", $term);

        /*
         * Remove ordenação anterior da listagem apenas quando existe busca.
         * Assim a relevância da busca fica acima da ordenação normal,
         * mas sem travar uma palavra específica.
         */
        $baseQuery = $query->getQuery();
        $baseQuery->orders = null;
        $baseQuery->unionOrders = null;

        $query->leftJoin('siamfesp_articles_categories as exact_cat', 'exact_cat.id', '=', 'siamfesp_articles.category_id');
        $query->select('siamfesp_articles.*');

        $query->orderByRaw("
            CASE
                WHEN LOWER(siamfesp_articles.title) = '{$termSql}' THEN 0
                WHEN LOWER(siamfesp_articles.alias) = '{$termSql}' THEN 1
                WHEN LOWER(siamfesp_articles.slug) = '{$termSql}' THEN 2
                WHEN LOWER(exact_cat.title) = '{$termSql}' THEN 3

                WHEN LOWER(siamfesp_articles.title) LIKE '{$termSql}%' THEN 4
                WHEN LOWER(exact_cat.title) LIKE '{$termSql}%' THEN 5

                WHEN LOWER(siamfesp_articles.title) LIKE '% {$termSql}%' THEN 6
                WHEN LOWER(exact_cat.title) LIKE '% {$termSql}%' THEN 7

                WHEN LOWER(siamfesp_articles.title) LIKE '%{$termSql}%' THEN 8
                WHEN LOWER(exact_cat.title) LIKE '%{$termSql}%' THEN 9

                ELSE 99
            END ASC
        ");

        $query->orderBy('siamfesp_articles.publish_up', 'desc');
        $query->orderBy('siamfesp_articles.id', 'desc');
    }

    protected function getAdminSearchTerm(): string
    {
        /*
         * Primeiro tenta pegar o termo direto do widget de listagem.
         * O OctoberCMS costuma manter o termo da busca na sessão do widget,
         * por isso o valor nem sempre vem em GET/POST.
         */
        try {
            if (method_exists($this, 'listGetWidget')) {
                $widget = $this->listGetWidget();

                if ($widget) {
                    if (method_exists($widget, 'getSearchTerm')) {
                        $term = trim((string) $widget->getSearchTerm());
                        if ($term !== '') {
                            return $term;
                        }
                    }

                    if (property_exists($widget, 'searchTerm')) {
                        $term = trim((string) $widget->searchTerm);
                        if ($term !== '') {
                            return $term;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Se o widget ainda não estiver pronto, continua tentando por request.
        }

        $candidates = [
            input('search'),
            post('search'),
            get('search'),
            input('searchTerm'),
            post('searchTerm'),
            input('filter[search]'),
            post('filter.search'),
            input('listToolbarSearch'),
            post('listToolbarSearch'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        /*
         * Busca defensiva dentro do POST/GET, pois algumas versões do backend
         * podem enviar o termo em estruturas internas do widget.
         */
        $found = $this->findSearchValueInArray((array) post());

        if ($found !== '') {
            return trim((string) $found);
        }

        $found = $this->findSearchValueInArray((array) get());

        return trim((string) $found);
    }

    protected function findSearchValueInArray(array $data): string
    {
        foreach ($data as $key => $value) {
            $keyText = strtolower((string) $key);

            if (is_string($value) && trim($value) !== '') {
                if (strpos($keyText, 'search') !== false || strpos($keyText, 'termo') !== false || strpos($keyText, 'busca') !== false) {
                    return trim($value);
                }
            }

            if (is_array($value)) {
                $found = $this->findSearchValueInArray($value);
                if ($found !== '') {
                    return $found;
                }
            }
        }

        return '';
    }

    public function onDelete()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há registros selecionados para excluir.');
            return $this->listRefresh();
        }

        foreach ($checkedIds as $recordId) {
            if (!$record = $this->formFindModelObject($recordId)) {
                continue;
            }

            $record->delete();
        }

        Flash::success('Registros excluídos com sucesso.');
        if (post('redirect')) {
            return \Backend::redirect('siamfesp/artigos/articles');
        }

        return $this->listRefresh();
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
