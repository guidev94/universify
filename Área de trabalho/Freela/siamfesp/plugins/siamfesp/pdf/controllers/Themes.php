<?php namespace Siamfesp\Pdf\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Siamfesp\Pdf\Models\Theme;
use ApplicationException;
use Exception;

class Themes extends Controller
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
        BackendMenu::setContext('Siamfesp.Pdf', 'pdf', 'themes');
    }

    public function onDelete()
    {
        $checkedIds = post('checked', []);

        if (!is_array($checkedIds) || !count($checkedIds)) {
            Flash::error('Não há registros selecionados para excluir.');
            return $this->listRefresh();
        }

        try {
            $blockedTitles = [];
            $deletedCount = 0;

            $themes = Theme::withCount('documents')->whereIn('id', $checkedIds)->get();

            foreach ($themes as $theme) {
                if ((int) $theme->documents_count > 0) {
                    $blockedTitles[] = $theme->title;
                    continue;
                }

                $theme->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                Flash::success($deletedCount === 1
                    ? 'Tema excluído com sucesso.'
                    : $deletedCount . ' temas excluídos com sucesso.');
            }

            if (count($blockedTitles)) {
                Flash::warning('Os seguintes temas não foram excluídos porque possuem documentos vinculados: ' . implode(', ', $blockedTitles) . '.');
            }
        } catch (Exception $e) {
            throw new ApplicationException('Erro ao excluir os temas: ' . $e->getMessage());
        }

        if (post('redirect')) {
            return \Backend::redirect('siamfesp/pdf/themes');
        }

        return $this->listRefresh();
    }

    public function reorder()
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('ReorderController')->reorder();
    }
}
