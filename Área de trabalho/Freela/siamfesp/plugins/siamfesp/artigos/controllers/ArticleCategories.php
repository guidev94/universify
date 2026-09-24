<?php namespace Siamfesp\Artigos\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use ApplicationException;

class ArticleCategories extends Controller
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
        BackendMenu::setContext('Siamfesp.Artigos', 'artigos', 'articlecategories');
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

            if ($record->children()->count() > 0) {
                throw new ApplicationException('Não é possível excluir uma categoria que possui subcategorias.');
            }
            if ($record->articles()->count() > 0) {
                throw new ApplicationException('Não é possível excluir uma categoria que possui artigos vinculados.');
            }

            $record->delete();
        }

        Flash::success('Registros excluídos com sucesso.');
        if (post('redirect')) {
            return \Backend::redirect('siamfesp/artigos/articlecategories');
        }

        return $this->listRefresh();
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
