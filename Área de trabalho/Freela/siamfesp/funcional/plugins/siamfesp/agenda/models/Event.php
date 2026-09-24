<?php namespace Siamfesp\Agenda\Models;

use Model;
use October\Rain\Database\Traits\Sluggable;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

class Event extends Model
{
    use Validation;
    use Sluggable;
    use Sortable;

    public $table = 'siamfesp_agenda_events';
    protected $guarded = [];
    protected $fillable = ['title','slug','description','location','event_date','end_date','link','image','is_published','sort_order'];
    protected $dates = ['event_date', 'end_date'];
    protected $appends = ['image_url', 'frontend_url'];

    public $rules = [
        'title' => 'required',
        'slug' => 'required|unique:siamfesp_agenda_events',
        'event_date' => 'required|date',
        'link' => 'nullable|url',
    ];

    public $customMessages = [
        'title.required' => 'Informe o título do evento.',
        'slug.required' => 'Informe o slug do evento.',
        'event_date.required' => 'Informe a data do evento.',
        'link.url' => 'Informe uma URL completa, por exemplo https://site.com.br.',
    ];

    public $slugs = ['slug' => 'title'];
    public $sortable = 'sort_order';
    public $defaultSort = ['event_date' => 'asc', 'sort_order' => 'asc'];

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('event_date')->orWhere('event_date', '>=', date('Y-m-d 00:00:00'));
        });
    }

    public function beforeValidate()
    {
        $this->title = trim((string) $this->title);
        $this->slug = trim((string) $this->slug);
    }

    public function beforeSave()
    {
        $this->description = trim((string) $this->description) ?: null;
        $this->location = trim((string) $this->location) ?: null;
        $this->link = trim((string) $this->link) ?: null;
        $this->image = $this->normalizeMediaPath($this->image);
        $this->is_published = (bool) $this->is_published;
    }

    public function getImageUrlAttribute(): string
    {
        $path = trim((string) $this->image);
        if ($path === '') return '';
        if (preg_match('#^(https?:)?//#i', $path)) return $path;
        if (strpos($path, 'storage/app/media/') === 0) return '/' . ltrim($path, '/');
        return url('/storage/app/media/' . ltrim($path, '/'));
    }

    public function getFrontendUrlAttribute(): string
    {
        return '/agenda/' . trim((string) $this->slug, '/');
    }

    protected function normalizeMediaPath($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (preg_match('#^https?://#i', $value)) return $value;
        $value = preg_replace('#^/?storage/app/media#', '', $value);
        return '/' . ltrim($value, '/');
    }
}
