<?php namespace Siamfesp\Newsletter\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Subscribers extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['siamfesp.newsletter.manage_subscribers'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Newsletter', 'newsletter');
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Selecione ao menos um inscrito para excluir.');
            return $this->listRefresh();
        }

        $deleted = 0;

        foreach ($checkedIds as $recordId) {
            if (!$record = $this->formFindModelObject($recordId)) {
                continue;
            }

            $record->delete();
            $deleted++;
        }

        if ($deleted > 0) {
            Flash::success($deleted === 1 ? 'Inscrito excluído com sucesso.' : $deleted . ' inscritos excluídos com sucesso.');
        } else {
            Flash::error('Nenhum inscrito foi excluído.');
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/newsletter/subscribers');
        }

        return $this->listRefresh();
    }
}
