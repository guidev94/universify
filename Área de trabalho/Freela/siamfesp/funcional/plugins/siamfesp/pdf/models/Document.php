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

    protected $appends = ['pdf_url', 'cover_url', 'public_link'];

    public $belongsTo = [
        'theme' => [Theme::class, 'key' => 'theme_id']
    ];

    public $rules = [
        'title' => 'required',
        'theme_id' => 'required',
        'pdf' => 'required_without:url|nullable',
        'url' => 'required_without:pdf|nullable|url',
        'year_month' => ['nullable', 'max:20'],
    ];

    public $customMessages = [
        'year_month.max' => 'O campo Mês/Ano deve ter no máximo 20 caracteres.',
        'theme_id.required' => 'Escolha um Tema antes de salvar.',
        'pdf.required_without' => 'Informe um PDF ou preencha o campo URL externa.',
        'url.required_without' => 'Informe uma URL externa ou selecione um PDF.',
        'url.url' => 'Informe uma URL completa e válida, por exemplo https://site.com/arquivo.pdf.',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];

    public function beforeSave()
    {
        $this->pdf = $this->normalizeMediaPath($this->pdf);
        $this->cover = $this->normalizeMediaPath($this->cover);
        $this->url = trim((string) $this->url) ?: null;
        $this->year_month = trim((string) $this->year_month) ?: null;
    }

    public function getPdfUrlAttribute()
    {
        return $this->makePublicUrl($this->pdf);
    }

    public function getCoverUrlAttribute()
    {
        return $this->makePublicUrl($this->cover);
    }

    public function getPublicLinkAttribute()
    {
        if ($this->url) {
            return $this->makePublicUrl($this->url, false);
        }

        return $this->getPdfUrlAttribute();
    }

    protected function normalizeMediaPath($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $value = preg_replace('#^/?storage/app/media#', '', $value);

        return '/' . ltrim($value, '/');
    }

    protected function makePublicUrl($value, $mediaLibrary = true)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (strpos($value, '/storage/app/media/') === 0) {
            return url($value);
        }

        if ($mediaLibrary) {
            return url('/storage/app/media/' . ltrim($value, '/'));
        }

        return url('/' . ltrim($value, '/'));
    }
}
