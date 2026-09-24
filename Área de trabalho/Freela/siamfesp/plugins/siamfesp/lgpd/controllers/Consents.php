<?php namespace Siamfesp\Lgpd\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Consents extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['siamfesp.lgpd.manage_consents'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Lgpd', 'lgpd');
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Selecione ao menos um consentimento para excluir.');
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
            Flash::success($deleted === 1 ? 'Consentimento excluído com sucesso.' : $deleted . ' consentimentos excluídos com sucesso.');
        } else {
            Flash::error('Nenhum consentimento foi excluído.');
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/lgpd/consents');
        }

        return $this->listRefresh();
    }
}
