<?php namespace Siamfesp\Pdf\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;

class Document extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_estudos_documents';

    protected $fillable = ['title', 'theme_id', 'year_month', 'url', 'pdf', 'cover', 'sort_order'];

    public $belongsTo = [
        'theme' => [Theme::class, 'key' => 'theme_id']
    ];

    public $rules = [
        'title' => 'required',
        'theme_id' => 'required',
        'pdf' => 'required_without:url',
        'url' => 'required_without:pdf|nullable|url',
        'year_month' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{4}$/'],
    ];

    public $customMessages = [
        'year_month.regex' => 'O campo Ano deve estar no formato MM/YYYY (ex: 01/2018).',
        'theme_id.required' => 'Escolha um Tema antes de salvar.',
        'pdf.required_without' => 'Informe um PDF ou preencha o campo URL externa.',
        'url.required_without' => 'Informe uma URL externa ou selecione um PDF.',
        'url.url' => 'Informe uma URL completa e válida, por exemplo https://site.com/arquivo.pdf.',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];
}
