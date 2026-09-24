<?php namespace Siamfesp\Associados\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Database\Traits\Sortable;

class AssociateCategory extends Model
{
    use Validation;
    use Sluggable;
    use Sortable;

    public $table = 'siamfesp_associados_categories';

    protected $guarded = [];

    protected $fillable = [
        'name',
        'slug',
        'is_published',
        'sort_order',
    ];

    public $rules = [
        'name' => 'required',
        'slug' => 'required|unique:siamfesp_associados_categories',
    ];

    public $slugs = [
        'slug' => 'name',
    ];

    public $hasMany = [
        'associates' => [Associate::class, 'key' => 'category_id'],
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
