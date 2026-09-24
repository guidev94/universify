<?php namespace Siamfesp\Menu\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class SiteUsers extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['siamfesp.menu.manage'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Menu', 'menu', 'siteusers');
    }

    public function create()
    {
        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        return $this->asExtension('FormController')->update($recordId);
    }

    public function onDelete()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há usuários selecionados para excluir.');
            return $this->listRefresh();
        }

        foreach ($checkedIds as $recordId) {
            if (!$record = $this->formFindModelObject($recordId)) {
                continue;
            }

            $record->delete();
        }

        Flash::success('Usuários excluídos com sucesso.');
        if (post('redirect')) {
            return \Backend::redirect('siamfesp/menu/siteusers');
        }

        return $this->listRefresh();
    }
}
