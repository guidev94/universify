<?php namespace Siamfesp\Artigos\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;

class ArticleCategory extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_articles_categories';

    protected $guarded = [];

    protected $fillable = [
        'parent_id', 'title', 'description', 'sort_order'
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
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
