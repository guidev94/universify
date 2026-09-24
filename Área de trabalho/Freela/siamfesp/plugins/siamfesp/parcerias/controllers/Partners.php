<?php namespace Siamfesp\Parcerias\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Partners extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    public $requiredPermissions = ['siamfesp.parcerias.manage_partners'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Parcerias', 'parcerias');
    }

    public function formExtendModel($model)
    {
        // Ao abrir um parceiro antigo, tenta mover imagens internas (/images/...)
        // para o campo de upload. URLs externas continuam no campo antigo.
        if ($model && $model->exists && method_exists($model, 'migrateLocalImageToUpload')) {
            $model->migrateLocalImageToUpload(true);
        }

        return $model;
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Selecione ao menos um parceiro para excluir.');
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
            Flash::success($deleted === 1 ? 'Parceiro excluído com sucesso.' : $deleted . ' parceiros excluídos com sucesso.');
        }
        else {
            Flash::error('Nenhum parceiro foi excluído.');
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/parcerias/partners');
        }

        return $this->listRefresh();
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
