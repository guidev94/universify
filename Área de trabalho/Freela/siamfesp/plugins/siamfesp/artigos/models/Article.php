<?php namespace Siamfesp\Artigos\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class Article extends Model
{
    use Validation;
    use AutoSlug;
    use Sortable;

    public $table = 'siamfesp_articles';

    protected $guarded = [];

    protected $fillable = [
        'category_id',
        'title',
        'alias',
        'slug',
        'introtext',
        'fulltext',
        'state',
        'is_published',
        'featured',
        'is_featured',
        'access',
        'hits',
        'publish_up',
        'publish_down',
        'language',
        'metakey',
        'metadesc',
        'images_json',
        'legacy_images_json',
        'image_intro_path',
        'image_fulltext_path',
        'image_intro_alt',
        'image_intro_caption',
        'image_fulltext_alt',
        'image_fulltext_caption',
        'urls_json',
        'sort_order',
        'joomla_id',
    ];

    protected $appends = [
        'frontend_url',
        'intro_image_url',
        'fulltext_image_url',
        'introtext_frontend',
        'fulltext_frontend',
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

    public function beforeValidate()
    {
        $this->title = trim((string) $this->title);
        $this->prepareAutomaticAliasAndSlug();
    }

    protected function prepareAutomaticAliasAndSlug(): void
    {
        $this->ensureAutomaticAliasAndSlug('alias', 'slug', 'title');
    }

    public function beforeSave()
    {
        $this->prepareAutomaticAliasAndSlug();

        // IMPORTANTE:
        // O campo "Imagem de introdução" agora salva somente na coluna image_intro_path.
        // O campo "JSON de imagens legado (Joomla)" permanece separado em images_json.
        // Assim, uma imagem nova cadastrada pelo CMS tem prioridade no site, mas os
        // registros antigos continuam buscando a imagem na pasta /images da raiz do FTP.
        $this->attributes['image_intro_path'] = $this->sanitizeMediaPath($this->attributes['image_intro_path'] ?? '');
        $this->attributes['image_fulltext_path'] = $this->sanitizeMediaPath($this->attributes['image_fulltext_path'] ?? '');
        $this->attributes['image_intro_alt'] = (string) ($this->attributes['image_intro_alt'] ?? '');
        $this->attributes['image_intro_caption'] = (string) ($this->attributes['image_intro_caption'] ?? '');
        $this->attributes['image_fulltext_alt'] = (string) ($this->attributes['image_fulltext_alt'] ?? '');
        $this->attributes['image_fulltext_caption'] = (string) ($this->attributes['image_fulltext_caption'] ?? '');
    }

    public function getFrontendUrlAttribute(): string
    {
        /*
         * Quando o artigo é criado no CMS e o campo slug/alias fica vazio,
         * o link não pode cair para /imprensa/ID. Geramos o slug pelo título
         * para manter a URL amigável e compatível com a página de detalhe.
         */
        $slug = trim((string) ($this->slug ?: $this->alias));

        if ($slug === '') {
            $slug = \Illuminate\Support\Str::slug((string) $this->title);
        }

        if ($slug === '') {
            $slug = (string) $this->id;
        }

        $categorySlug = 'noticias';
        if ($this->category) {
            $categorySlug = trim((string) ($this->category->slug ?? '')) ?: $categorySlug;

            $parent = null;
            try {
                $parent = $this->category->parent;
            } catch (\Throwable $e) {
                $parent = null;
            }

            if ($parent) {
                $parentSlug = trim((string) ($parent->slug ?? ''));
                if ($parentSlug !== '') {
                    $categorySlug = $parentSlug;
                }
            }
        }

        $normalizedCategorySlug = trim((string) $categorySlug, '/');
        if (in_array($normalizedCategorySlug, ['novas-parcerias', 'novas-parceirias', 'nossas-parcerias'], true)) {
            return '/novas-parceirias/' . trim($slug, '/');
        }

        return '/' . trim($categorySlug, '/') . '/' . trim($slug, '/');
    }

    public function getIntroImageUrlAttribute(): string
    {
        $cmsImage = $this->getStoredAttribute('image_intro_path');
        if ($cmsImage !== '') {
            return $this->normalizeCmsImageForFrontend($cmsImage);
        }

        $legacyImage = $this->resolveLegacyImagePath('image_intro');
        if ($legacyImage !== '') {
            return $this->normalizeLegacyImageForFrontend($legacyImage);
        }

        $htmlImage = $this->extractFirstImageFromHtml($this->getStoredAttribute('introtext'));
        return $htmlImage !== '' ? $this->normalizeLegacyImageForFrontend($htmlImage) : '';
    }

    public function getFulltextImageUrlAttribute(): string
    {
        $cmsImage = $this->getStoredAttribute('image_fulltext_path');
        if ($cmsImage !== '') {
            return $this->normalizeCmsImageForFrontend($cmsImage);
        }

        $legacyImage = $this->resolveLegacyImagePath('image_fulltext');
        if ($legacyImage !== '') {
            return $this->normalizeLegacyImageForFrontend($legacyImage);
        }

        $htmlImage = $this->extractFirstImageFromHtml($this->getStoredAttribute('fulltext'));
        return $htmlImage !== '' ? $this->normalizeLegacyImageForFrontend($htmlImage) : '';
    }

    public function getIntrotextFrontendAttribute(): string
    {
        return $this->rewriteContentImageUrls($this->getStoredAttribute('introtext'));
    }

    public function getFulltextFrontendAttribute(): string
    {
        return $this->rewriteContentImageUrls($this->getStoredAttribute('fulltext'));
    }

    public function getImageIntroAltAttribute($value)
    {
        $value = (string) $value;
        if ($value !== '') {
            return $value;
        }

        $payload = $this->getLegacyImagesPayload();
        return (string) ($payload['image_intro_alt'] ?? '');
    }

    public function getImageIntroCaptionAttribute($value)
    {
        $value = (string) $value;
        if ($value !== '') {
            return $value;
        }

        $payload = $this->getLegacyImagesPayload();
        return (string) ($payload['image_intro_caption'] ?? '');
    }

    public function getImageFulltextAltAttribute($value)
    {
        $value = (string) $value;
        if ($value !== '') {
            return $value;
        }

        $payload = $this->getLegacyImagesPayload();
        return (string) ($payload['image_fulltext_alt'] ?? '');
    }

    public function getImageFulltextCaptionAttribute($value)
    {
        $value = (string) $value;
        if ($value !== '') {
            return $value;
        }

        $payload = $this->getLegacyImagesPayload();
        return (string) ($payload['image_fulltext_caption'] ?? '');
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

    protected function getStoredAttribute(string $name): string
    {
        $value = $this->attributes[$name] ?? $this->original[$name] ?? '';
        return trim((string) $value);
    }

    protected function resolveLegacyImagePath(string $key): string
    {
        $payload = $this->getLegacyImagesPayload();

        $path = $this->extractPlainImagePath($payload[$key] ?? '');
        if ($path !== '') {
            return $path;
        }

        if ($key === 'image_intro') {
            $path = $this->extractPlainImagePath($payload['intro'] ?? '');
            if ($path !== '') {
                return $path;
            }
        }

        return '';
    }

    protected function getLegacyImagesPayload(): array
    {
        $payload = [];

        foreach (['images_json', 'legacy_images_json'] as $column) {
            $raw = $this->attributes[$column] ?? $this->original[$column] ?? '';
            $parsed = $this->parseImagesPayload($raw);

            if ($parsed) {
                $payload = array_replace($payload, $parsed);
            }
        }

        return $payload;
    }

    protected function parseImagesPayload($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!$raw || !is_string($raw)) {
            return [];
        }

        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if ($this->looksLikeImagePath($raw)) {
            return ['image_intro' => $raw];
        }

        if (preg_match('~(https?://[^\s"\']+?\.(?:jpe?g|png|gif|webp|svg)|/?(?:images|storage/app/media|storage/app/uploads|media)/[^\s"\']+?\.(?:jpe?g|png|gif|webp|svg))~iu', $raw, $match)) {
            return ['image_intro' => $match[1]];
        }

        return [];
    }

    protected function extractFirstImageFromHtml(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        if (preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', $html, $match)) {
            return $this->extractPlainImagePath(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    protected function looksLikeImagePath(?string $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('~^(https?://|/)?[^?"\'<>
]+\.(?:jpe?g|png|gif|webp|svg)(?:[?#].*)?$~iu', $value);
    }

    protected function extractPlainImagePath(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        if ($value === '') {
            return '';
        }

        $parts = explode('#', $value, 2);
        $path = urldecode(trim($parts[0]));
        $path = str_replace('\\', '/', $path);

        return trim($path);
    }

    protected function sanitizeMediaPath(?string $value): string
    {
        $path = html_entity_decode(trim((string) $value), ENT_QUOTES, 'UTF-8');
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('~^/+~', '', $path);

        return $path;
    }

    protected function rewriteContentImageUrls(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return (string) preg_replace_callback("~(<img\\b[^>]*?\\bsrc=[\"'])([^\"']+)([\"'])~i", function ($match) {
            $src = html_entity_decode(trim((string) $match[2]), ENT_QUOTES, 'UTF-8');

            if ($src === '' || preg_match('~^(https?:)?//~i', $src) || preg_match('~^data:image/~i', $src)) {
                return $match[1] . $match[2] . $match[3];
            }

            $path = str_replace('\\', '/', $src);
            $path = preg_replace('~^/+~', '', $path);

            if (strpos($path, 'images/') === 0) {
                return $match[1] . '/images/' . ltrim(substr($path, strlen('images/')), '/') . $match[3];
            }

            if (strpos($path, 'storage/app/media/') === 0 || strpos($path, 'storage/app/uploads/') === 0) {
                return $match[1] . '/' . ltrim($path, '/') . $match[3];
            }

            if (strpos($path, 'media/') === 0) {
                return $match[1] . $this->normalizeCmsImageForFrontend($path) . $match[3];
            }

            return $match[1] . $match[2] . $match[3];
        }, $html);
    }

    protected function normalizeCmsImageForFrontend(?string $path): string
    {
        $original = trim((string) $path);
        if ($original === '') {
            return '';
        }

        if (preg_match('~^(https?:)?//~i', $original) || preg_match('~^data:image/~i', $original)) {
            return $original;
        }

        $path = $this->sanitizeMediaPath($original);
        if ($path === '') {
            return '';
        }

        // Campo "Imagem de introdução" / "Imagem do conteúdo" vem do Media Library.
        // Mesmo quando o caminho salvo começa com "images/", ele deve apontar para
        // /storage/app/media/images/... e NÃO para /images/... da raiz do Joomla.
        if (strpos($path, 'storage/app/media/') === 0 || strpos($path, 'storage/app/uploads/') === 0) {
            return '/' . ltrim($path, '/');
        }

        if (strpos($path, 'media/') === 0) {
            $path = substr($path, strlen('media/'));
        }

        foreach ([
            '\\System\\Classes\\MediaLibrary',
            '\\Cms\\Classes\\MediaLibrary',
        ] as $mediaLibraryClass) {
            if (class_exists($mediaLibraryClass) && method_exists($mediaLibraryClass, 'url')) {
                return $mediaLibraryClass::url('/' . ltrim($path, '/'));
            }
        }

        return url('/storage/app/media/' . ltrim($path, '/'));
    }

    protected function normalizeLegacyImageForFrontend(?string $path): string
    {
        $original = trim((string) $path);
        if ($original === '') {
            return '';
        }

        if (preg_match('~^(https?:)?//~i', $original) || preg_match('~^data:image/~i', $original)) {
            return $original;
        }

        $path = $this->sanitizeMediaPath($original);
        if ($path === '') {
            return '';
        }

        if (strpos($path, 'storage/app/media/') === 0 || strpos($path, 'storage/app/uploads/') === 0) {
            return '/' . ltrim($path, '/');
        }

        if (strpos($path, 'media/') === 0) {
            return $this->normalizeCmsImageForFrontend($path);
        }

        // Imagens migradas do Joomla ficam na pasta /images da raiz do FTP.
        if (strpos($path, 'images/') === 0) {
            return '/' . ltrim($path, '/');
        }

        // Caso o legado tenha vindo somente com o nome do arquivo, assume /images.
        return '/images/' . ltrim($path, '/');
    }
}
