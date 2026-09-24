<?php namespace Siamfesp\Banners\Controllers;

use Backend\Classes\Controller;
use Backend;
use BackendMenu;
use Flash;

class InternalBanners extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $requiredPermissions = [
        'siamfesp.banners.manage_internal_banners',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Siamfesp.Banners', 'banners', 'internalbanners');
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
    }

    public function onDelete()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há banners internos selecionados para excluir.');
            return $this->listRefresh();
        }

        foreach ($checkedIds as $recordId) {
            if (!$record = $this->formFindModelObject($recordId)) {
                continue;
            }

            $record->delete();
        }

        Flash::success('Banners internos excluídos com sucesso.');

        if (post('redirect')) {
            return Backend::redirect('siamfesp/banners/internalbanners');
        }

        return $this->listRefresh();
    }
}
