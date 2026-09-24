<?php namespace Siamfesp\Artigos\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;

class Article extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_articles';

    protected $guarded = [];

    protected $fillable = [
        'category_id',
        'title',
        'alias',
        'introtext',
        'fulltext',
        'state',
        'featured',
        'access',
        'hits',
        'publish_up',
        'publish_down',
        'language',
        'metakey',
        'metadesc',
        'images_json',
        'urls_json',
        'sort_order',
        'joomla_id',
    ];

    protected $appends = [
        'image_intro_path',
        'image_fulltext_path',
        'image_intro_alt',
        'image_intro_caption',
        'image_fulltext_alt',
        'image_fulltext_caption',
    ];

    protected $purgeable = [
        'image_intro_path',
        'image_fulltext_path',
        'image_intro_alt',
        'image_intro_caption',
        'image_fulltext_alt',
        'image_fulltext_caption',
    ];

    public $belongsTo = [
        'category' => [ArticleCategory::class, 'key' => 'category_id'],
    ];

    public $rules = [
        'title' => 'required',
        'category_id' => 'required',
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'desc'];

    public function afterFetch()
    {
        $this->fillImageAttributesFromJson();
    }

    public function beforeSave()
    {
        $payload = $this->parseImagesJson();

        $introPath = $this->attributes['image_intro_path'] ?? null;
        $introAlt = $this->attributes['image_intro_alt'] ?? '';
        $introCaption = $this->attributes['image_intro_caption'] ?? '';

        $fullPath = $this->attributes['image_fulltext_path'] ?? null;
        $fullAlt = $this->attributes['image_fulltext_alt'] ?? '';
        $fullCaption = $this->attributes['image_fulltext_caption'] ?? '';

        $payload['image_intro'] = $this->normalizeImageValue(
            $introPath,
            $payload['image_intro'] ?? ''
        );
        $payload['image_intro_alt'] = (string) $introAlt;
        $payload['image_intro_caption'] = (string) $introCaption;

        $payload['image_fulltext'] = $this->normalizeImageValue(
            $fullPath,
            $payload['image_fulltext'] ?? ''
        );
        $payload['image_fulltext_alt'] = (string) $fullAlt;
        $payload['image_fulltext_caption'] = (string) $fullCaption;

        $payload['float_intro'] = (string) ($payload['float_intro'] ?? '');
        $payload['float_fulltext'] = (string) ($payload['float_fulltext'] ?? '');

        $this->attributes['images_json'] = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        unset(
            $this->attributes['image_intro_path'],
            $this->attributes['image_fulltext_path'],
            $this->attributes['image_intro_alt'],
            $this->attributes['image_intro_caption'],
            $this->attributes['image_fulltext_alt'],
            $this->attributes['image_fulltext_caption']
        );
    }

    public function getImageIntroPathAttribute()
    {
        $payload = $this->parseImagesJson();
        return $this->extractPlainImagePath($payload['image_intro'] ?? '');
    }

    public function setImageIntroPathAttribute($value)
    {
        $this->attributes['image_intro_path'] = $this->sanitizeMediaPath($value);
    }

    public function getImageFulltextPathAttribute()
    {
        $payload = $this->parseImagesJson();
        return $this->extractPlainImagePath($payload['image_fulltext'] ?? '');
    }

    public function setImageFulltextPathAttribute($value)
    {
        $this->attributes['image_fulltext_path'] = $this->sanitizeMediaPath($value);
    }

    public function getImageIntroAltAttribute()
    {
        $payload = $this->parseImagesJson();
        return (string) ($payload['image_intro_alt'] ?? '');
    }

    public function setImageIntroAltAttribute($value)
    {
        $this->attributes['image_intro_alt'] = (string) $value;
    }

    public function getImageIntroCaptionAttribute()
    {
        $payload = $this->parseImagesJson();
        return (string) ($payload['image_intro_caption'] ?? '');
    }

    public function setImageIntroCaptionAttribute($value)
    {
        $this->attributes['image_intro_caption'] = (string) $value;
    }

    public function getImageFulltextAltAttribute()
    {
        $payload = $this->parseImagesJson();
        return (string) ($payload['image_fulltext_alt'] ?? '');
    }

    public function setImageFulltextAltAttribute($value)
    {
        $this->attributes['image_fulltext_alt'] = (string) $value;
    }

    public function getImageFulltextCaptionAttribute()
    {
        $payload = $this->parseImagesJson();
        return (string) ($payload['image_fulltext_caption'] ?? '');
    }

    public function setImageFulltextCaptionAttribute($value)
    {
        $this->attributes['image_fulltext_caption'] = (string) $value;
    }

    public function getIntroImageUrlAttribute()
    {
        return $this->normalizeForFrontend($this->image_intro_path);
    }

    public function getFulltextImageUrlAttribute()
    {
        return $this->normalizeForFrontend($this->image_fulltext_path);
    }

    protected function fillImageAttributesFromJson(): void
    {
        $payload = $this->parseImagesJson();

        $this->attributes['image_intro_path'] = $this->extractPlainImagePath($payload['image_intro'] ?? '');
        $this->attributes['image_fulltext_path'] = $this->extractPlainImagePath($payload['image_fulltext'] ?? '');
        $this->attributes['image_intro_alt'] = (string) ($payload['image_intro_alt'] ?? '');
        $this->attributes['image_intro_caption'] = (string) ($payload['image_intro_caption'] ?? '');
        $this->attributes['image_fulltext_alt'] = (string) ($payload['image_fulltext_alt'] ?? '');
        $this->attributes['image_fulltext_caption'] = (string) ($payload['image_fulltext_caption'] ?? '');
    }

    public function scopeApplyBackendSearchOrder($query, $term = null)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query->orderBy('title', 'asc');
        }

        $termLike = '%' . $term . '%';
        $termStart = $term . '%';

        return $query
            ->orderByRaw(
                "
            CASE
            WHEN LOWER(title) = LOWER(?) THEN 0
            WHEN LOWER(title) LIKE LOWER(?) THEN 1
            WHEN LOWER(title) LIKE LOWER(?) THEN 2
            ELSE 3
            END
            ",
                [$term, $termStart, $termLike]
            )
            ->orderBy('title', 'asc');
    }

    protected function parseImagesJson(): array
    {
        $raw = $this->attributes['images_json'] ?? $this->original['images_json'] ?? '';

        if (is_array($raw)) {
            return $raw;
        }

        if (!$raw || !is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function extractPlainImagePath(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $parts = explode('#', $value, 2);
        $path = urldecode(trim($parts[0]));

        return str_replace('\\', '/', $path);
    }

    protected function normalizeImageValue(?string $value, ?string $currentValue = null): string
    {
        $path = $this->sanitizeMediaPath($value);
        if ($path === '') {
            return '';
        }

        $suffix = '';
        $currentValue = (string) $currentValue;

        if ($currentValue !== '' && str_contains($currentValue, '#')) {
            [, $suffix] = explode('#', $currentValue, 2);
            $suffix = '#' . $suffix;
        }

        return $path . $suffix;
    }

    protected function sanitizeMediaPath(?string $value): string
    {
        $path = trim((string) $value);
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^/+#', '', $path);

        return $path;
    }

    protected function normalizeForFrontend(?string $path): string
    {
        $path = $this->sanitizeMediaPath($path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}
