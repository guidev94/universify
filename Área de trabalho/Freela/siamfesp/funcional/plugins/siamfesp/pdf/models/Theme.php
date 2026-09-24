<?php namespace Siamfesp\Pdf\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use System\Models\File;
use October\Rain\Database\Traits\Sortable;

class Theme extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_estudos_themes';

    protected $fillable = ['title', 'sort_order'];

    public $attachOne = [
        'image' => File::class,
    ];

    public $hasMany = [
        'documents' => [Document::class, 'key' => 'theme_id']
    ];

    public $rules = [
        'title' => 'required',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
