<?php namespace Siamfesp\Menu\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class Menu extends Model
{
    use Validation;
    use AutoSlug;
    use Sortable;

    public $table = 'siamfesp_menus';

    protected $guarded = [];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_published',
        'sort_order',
    ];

    public $rules = [
        'title' => 'required',
        'slug'  => 'required',
    ];

    public $hasMany = [
        'items' => [MenuItem::class, 'key' => 'menu_id'],
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];

    public function beforeValidate()
    {
        $this->title = trim((string) $this->title);
        $this->ensureAutomaticSlug('slug', 'title');
    }

    public function beforeSave()
    {
        $this->ensureAutomaticSlug('slug', 'title');
    }

}
