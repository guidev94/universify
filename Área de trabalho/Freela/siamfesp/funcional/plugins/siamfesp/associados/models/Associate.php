<?php namespace Siamfesp\Associados\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Database\Traits\Sortable;

class Associate extends Model
{
    use Validation;
    use Sluggable;
    use Sortable;

    public $table = 'siamfesp_associados';

    protected $guarded = [];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'image',
        'image_alt',
        'associate_link',
        'is_published',
        'sort_order',
    ];

    public $rules = [
        'category_id' => 'required',
        'name' => 'required',
        'image' => 'required',
        'slug' => 'required|unique:siamfesp_associados',
        'associate_link' => 'nullable|url',
    ];

    public $slugs = [
        'slug' => 'name',
    ];

    public $belongsTo = [
        'category' => [AssociateCategory::class, 'key' => 'category_id'],
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
