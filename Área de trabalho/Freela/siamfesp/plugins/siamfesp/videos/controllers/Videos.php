<?php namespace Siamfesp\Videos\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;

class Videos extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ReorderController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    public $requiredPermissions = ['siamfesp.videos.manage_videos'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Videos', 'videos');
    }

    public function create()
    {
        $this->bodyClass = 'compact-container';
        $this->initForm($this->formCreateModelObject());
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->initForm($this->formFindModelObject($recordId));
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Selecione ao menos um vídeo para excluir.');
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
            Flash::success($deleted === 1 ? 'Vídeo excluído com sucesso.' : $deleted . ' vídeos excluídos com sucesso.');
        } else {
            Flash::error('Nenhum vídeo foi excluído.');
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/videos/videos');
        }

        return $this->listRefresh();
    }
}
