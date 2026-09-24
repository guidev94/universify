<?php namespace Siamfesp\Videos\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class Video extends Model
{
    use Validation;
    use AutoSlug;
    use Sortable;

    public $table = 'siamfesp_videos_videos';
    protected $guarded = [];
    protected $fillable = [
        'title', 'slug', 'source_type', 'youtube_url', 'video_file', 'thumbnail',
        'description', 'is_featured', 'is_published', 'sort_order', 'published_at'
    ];
    protected $dates = ['published_at'];
    protected $appends = ['frontend_url', 'thumbnail_url', 'video_url', 'youtube_id', 'embed_url', 'player_html', 'short_description'];
    public $sortable = 'sort_order';

    public $rules = [
        'title' => 'required',
        'slug' => 'required',
        'source_type' => 'required',
    ];

    public $customMessages = [
        'title.required' => 'Informe o título do vídeo.',
        'slug.required' => 'Informe o slug do vídeo.',
        'source_type.required' => 'Selecione a origem do vídeo.',
    ];

    public function getSourceTypeOptions(): array
    {
        return [
            'youtube' => 'YouTube',
            'upload' => 'Vídeo enviado / Media Library',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function beforeValidate()
    {
        $this->normalizeBasicFields();
        $this->ensureAutomaticSlug('slug', 'title');
    }

    public function beforeSave()
    {
        $this->normalizeBasicFields();
        $this->ensureAutomaticSlug('slug', 'title');
        $this->youtube_url = trim((string) $this->youtube_url) ?: null;
        $this->video_file = $this->normalizeMediaPath($this->video_file);
        $this->thumbnail = $this->normalizeMediaPath($this->thumbnail);
        $this->description = trim((string) $this->description) ?: null;
        $this->is_featured = (bool) $this->is_featured;
        $this->is_published = (bool) $this->is_published;
    }

    protected function normalizeBasicFields(): void
    {
        $this->title = trim((string) $this->title);
        $this->slug = trim((string) $this->slug);
        $this->source_type = in_array($this->source_type, ['youtube', 'upload'], true) ? $this->source_type : 'youtube';
    }

    public function getFrontendUrlAttribute(): string
    {
        return '/videos/' . trim((string) $this->slug, '/');
    }

    public function getVideoUrlAttribute(): string
    {
        return $this->mediaUrl($this->video_file);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $thumb = $this->mediaUrl($this->thumbnail);
        if ($thumb) {
            return $thumb;
        }

        $youtubeId = $this->getYoutubeIdAttribute();
        if ($youtubeId) {
            return 'https://i.ytimg.com/vi/' . $youtubeId . '/hqdefault.jpg';
        }

        return '';
    }

    public function getYoutubeIdAttribute(): string
    {
        return $this->extractYoutubeId((string) $this->youtube_url);
    }

    public function getEmbedUrlAttribute(): string
    {
        $youtubeId = $this->getYoutubeIdAttribute();
        if ($youtubeId) {
            return 'https://www.youtube.com/embed/' . $youtubeId . '?rel=0&modestbranding=1';
        }

        return '';
    }

    public function getPlayerHtmlAttribute(): string
    {
        if ($this->source_type === 'youtube' && $this->embed_url) {
            return '<iframe src="' . e($this->embed_url) . '" title="' . e($this->title) . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        }

        if ($this->source_type === 'upload' && $this->video_url) {
            $poster = $this->thumbnail_url ? ' poster="' . e($this->thumbnail_url) . '"' : '';
            return '<video controls playsinline preload="metadata"' . $poster . '><source src="' . e($this->video_url) . '">Seu navegador não suporta vídeo HTML5.</video>';
        }

        return '';
    }

    public function getShortDescriptionAttribute(): string
    {
        $text = strip_tags((string) $this->description);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > 150) {
            return rtrim(mb_substr($text, 0, 147, 'UTF-8')) . '...';
        }

        return $text;
    }

    protected function mediaUrl($value): string
    {
        $path = trim((string) $value);
        if ($path === '') return '';
        if (preg_match('#^(https?:)?//#i', $path)) return $path;
        if (strpos($path, 'storage/app/media/') === 0) return '/' . ltrim($path, '/');
        return url('/storage/app/media/' . ltrim($path, '/'));
    }

    protected function normalizeMediaPath($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (preg_match('#^https?://#i', $value)) return $value;
        $value = preg_replace('#^/?storage/app/media#', '', $value);
        return '/' . ltrim($value, '/');
    }

    protected function extractYoutubeId(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        if (preg_match('#^[a-zA-Z0-9_-]{11}$#', $url)) {
            return $url;
        }

        $patterns = [
            '#youtu\.be/([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/embed/([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/watch\?[^\s]*v=([a-zA-Z0-9_-]{11})#',
            '#youtube\.com/.*[?&]v=([a-zA-Z0-9_-]{11})#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        $parts = parse_url($url);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['v']) && preg_match('#^[a-zA-Z0-9_-]{11}$#', $query['v'])) {
                return $query['v'];
            }
        }

        return '';
    }
}
