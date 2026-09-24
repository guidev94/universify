<?php namespace Siamfesp\Associados\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Database\Traits\Sortable;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class Associate extends Model
{
    use Validation;
    use AutoSlug;
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
        'slug' => 'required',
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

    public function beforeValidate()
    {
        $this->name = trim((string) $this->name);
        $this->ensureAutomaticSlug('slug', 'name', ['maxLength' => 390]);
    }

    public function beforeSave()
    {
        $this->ensureAutomaticSlug('slug', 'name', ['maxLength' => 390]);
    }

}
