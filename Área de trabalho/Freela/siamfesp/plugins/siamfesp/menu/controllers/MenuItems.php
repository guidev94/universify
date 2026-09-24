<?php namespace Siamfesp\Menu\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class MenuItems extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Menu', 'menu', 'menuitems');
    }

    public function create()
    {
        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        return $this->asExtension('FormController')->update($recordId);
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
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
            return \Backend::redirect('siamfesp/menu/menuitems');
        }

        return $this->listRefresh();
    }
}
