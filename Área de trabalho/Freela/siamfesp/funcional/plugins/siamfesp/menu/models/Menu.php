<?php namespace Siamfesp\Menu\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

class Menu extends Model
{
    use Validation;
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
        'slug'  => 'required|unique:siamfesp_menus',
    ];

    public $hasMany = [
        'items' => [MenuItem::class, 'key' => 'menu_id'],
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
