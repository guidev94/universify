<?php namespace Siamfesp\Pdf\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Siamfesp\Pdf\Models\Document;
use ApplicationException;
use Exception;

class Documents extends Controller
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
        BackendMenu::setContext('Siamfesp.Pdf', 'pdf', 'documents');
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há registros selecionados para excluir.');
            return $this->listRefresh();
        }

        try {
            $deletedCount = Document::whereIn('id', $checkedIds)->delete();

            if ($deletedCount > 0) {
                Flash::success($deletedCount === 1
                    ? 'Documento excluído com sucesso.'
                    : $deletedCount . ' documentos excluídos com sucesso.');
            }
        } catch (Exception $e) {
            throw new ApplicationException('Erro ao excluir os documentos: ' . $e->getMessage());
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/pdf/documents');
        }

        return $this->listRefresh();
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
