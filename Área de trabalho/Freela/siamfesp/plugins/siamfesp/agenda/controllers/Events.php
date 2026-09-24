<?php namespace Siamfesp\Agenda\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Events extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    public $requiredPermissions = ['siamfesp.agenda.manage_events'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Agenda', 'agenda');
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há registros selecionados para excluir.');
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

        Flash::success($deleted === 1 ? 'Registro excluído com sucesso.' : $deleted . ' registros excluídos com sucesso.');

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/agenda/events');
        }

        return $this->listRefresh();
    }


    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
