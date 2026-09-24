<?php namespace Siamfesp\Banners\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

class Banner extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_banners';

    protected const ALLOWED_BUTTON_TARGETS = [
        '_self',
        '_blank',
        '_parent',
        '_top',
    ];

    protected $guarded = [];

    protected $fillable = [
        'title_1',
        'title_2',
        'description',
        'image',
        'is_image_only',
        'button_text',
        'button_link',
        'button_target',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_image_only' => 'boolean',
        'is_published'  => 'boolean',
    ];

    public $rules = [
        'title_1'       => 'required|max:255',
        'image'         => 'required',
        'button_target' => 'nullable|in:_self,_blank,_parent,_top',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
    }

    public function getButtonTargetOptions(): array
    {
        return [
            '_self'   => 'Mesma aba (_self)',
            '_blank'  => 'Nova aba (_blank)',
            '_parent' => 'Janela pai (_parent)',
            '_top'    => 'Janela superior (_top)',
        ];
    }

    public function getSafeButtonTargetAttribute(): string
    {
        $target = trim((string) $this->button_target);

        if (!in_array($target, self::ALLOWED_BUTTON_TARGETS, true)) {
            return '_self';
        }

        return $target;
    }

    public function getSafeButtonRelAttribute(): string
    {
        return $this->safe_button_target === '_blank' ? 'noopener noreferrer' : '';
    }

    public function beforeValidate()
    {
        $this->title_1 = trim((string) $this->title_1);
    }

    public function beforeSave()
    {
        $this->is_image_only = (bool) $this->is_image_only;

        if (!in_array((string) $this->button_target, self::ALLOWED_BUTTON_TARGETS, true)) {
            $this->button_target = '_self';
        }
    }

    public function getImageUrlAttribute(): string
    {
        $path = $this->normalizeMediaPath($this->image);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/' . ltrim($path, '/');
        }

        return '/storage/app/media/' . ltrim($path, '/');
    }

    protected function normalizeMediaPath(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        return str_replace('\\', '/', $path);
    }
}
