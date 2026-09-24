<?php namespace Siamfesp\Artigos\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class ArticleCategory extends Model
{
    use Validation;
    use AutoSlug;
    use Sortable;

    public $table = 'siamfesp_articles_categories';

    protected $guarded = [];

    protected $fillable = [
        'parent_id', 'title', 'slug', 'description', 'sort_order'
    ];

    public $belongsTo = [
        'parent' => [self::class, 'key' => 'parent_id'],
    ];

    public $hasMany = [
        'children' => [self::class, 'key' => 'parent_id'],
        'articles' => [Article::class, 'key' => 'category_id'],
    ];

    public $rules = [
        'title' => 'required',
        'slug' => 'required',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];

    public function beforeValidate()
    {
        $this->title = trim((string) $this->title);
        $this->ensureAutomaticSlug('slug', 'title', ['maxLength' => 390]);
    }

    public function beforeSave()
    {
        $this->ensureAutomaticSlug('slug', 'title', ['maxLength' => 390]);
    }

}
