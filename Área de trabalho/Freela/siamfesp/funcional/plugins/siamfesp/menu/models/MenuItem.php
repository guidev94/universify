<?php namespace Siamfesp\Menu\Models;

use Cms\Classes\Page;
use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Artigos\Models\Article;
use Siamfesp\Artigos\Models\ArticleCategory;
use Str;

class MenuItem extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_menu_items';

    protected $guarded = [];

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'slug',
        'type',
        'page_name',
        'page_param',
        'article_id',
        'category_id',
        'dynamic_source',
        'dynamic_record_id',
        'url',
        'css_class',
        'target_blank',
        'is_published',
        'sort_order',
    ];

    protected $appends = ['frontend_url', 'should_open_blank'];

    public $rules = [
        'menu_id' => 'required',
        'title'   => 'required',
        'type'    => 'required|in:page,article,category,plugin,url',
    ];

    public $belongsTo = [
        'menu'     => [Menu::class, 'key' => 'menu_id'],
        'parent'   => [self::class, 'key' => 'parent_id'],
        'article'  => [Article::class, 'key' => 'article_id'],
        'category' => [ArticleCategory::class, 'key' => 'category_id'],
    ];

    public $hasMany = [
        'children' => [self::class, 'key' => 'parent_id'],
    ];

    public $sortable = 'sort_order';

    public $defaultSort = ['sort_order' => 'asc', 'id' => 'asc'];

    public function getTypeOptions()
    {
        return [
            'page'     => 'Página CMS',
            'article'  => 'Artigo',
            'category' => 'Categoria',
            'plugin'   => 'Associados / Outros plugins',
            'url'      => 'URL externa',
        ];
    }

    public function getPageNameOptions()
    {
        $options = [];
        foreach (Page::sortBy('baseFileName')->all() as $page) {
            $options[$page->baseFileName] = $page->title
            ? ($page->title . ' (' . $page->baseFileName . ')')
            : $page->baseFileName;
        }
        return $options;
    }

    public function getDynamicSourceOptions(): array
    {
        $options = [];

        foreach ($this->getDynamicSourcesConfig() as $key => $config) {
            $options[$key] = $config['label'] ?? $key;
        }

        return $options;
    }

    public function getDynamicRecordIdOptions(): array
    {
        $sourceKey = $this->dynamic_source ?: post('MenuItem[dynamic_source]') ?: post('dynamic_source');
        $source = $this->getDynamicSourceConfig($sourceKey);

        if (!$source) {
            return [];
        }

        $modelClass = $source['model'] ?? null;
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        $titleColumn = $source['titleColumn'] ?? 'title';
        $publishedColumn = $source['publishedColumn'] ?? null;
        $orderBy = $source['orderBy'] ?? [[$titleColumn, 'asc']];

        $query = $modelClass::query();

        if ($publishedColumn && $this->hasColumn($modelClass, $publishedColumn)) {
            $query->where($publishedColumn, 1);
        }

        foreach ($orderBy as $orderRule) {
            $column = $orderRule[0] ?? null;
            $direction = strtolower($orderRule[1] ?? 'asc');
            if ($column && $this->hasColumn($modelClass, $column)) {
                $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
            }
        }

        return $query->get()->mapWithKeys(function ($record) use ($titleColumn) {
            $label = trim((string) data_get($record, $titleColumn));
            if ($label === '') {
                $label = 'Registro #' . $record->getKey();
            }

            return [$record->getKey() => $label];
        })->all();
    }

    public function getFrontendUrlAttribute(): string
    {
        return $this->buildFrontendUrl();
    }

    public function getShouldOpenBlankAttribute(): bool
    {
        $url = trim((string) $this->frontend_url);

        if ((bool) $this->target_blank) {
            return true;
        }

        if ($url === '' || $url === '#') {
            return false;
        }

        return (bool) (
            preg_match('#^(https?:)?//#i', $url) ||
            preg_match('#\.(pdf|doc|docx|xls|xlsx|ppt|pptx)(\?.*)?$#i', $url) ||
            strpos($url, '/storage/app/media/') === 0
        );
    }

    public function buildFrontendUrl(): string
    {
        $parentSlug = $this->parent ? ($this->parent->slug ?: Str::slug($this->parent->title)) : null;
        $selfSlug   = $this->slug ?: Str::slug($this->title);

        switch ($this->type) {
            case 'page':
            $pageName = trim((string) $this->page_name);

            if ($pageName !== '') {
                $pageUrl = $this->buildCmsPageUrl($pageName, [
                    'cat' => $parentSlug ?: $selfSlug,
                    'categoria' => $selfSlug,
                    'slug' => $selfSlug,
                    trim((string) ($this->page_param ?: 'slug')) => $selfSlug,
                ]);

                if ($pageUrl !== '') {
                    return $pageUrl;
                }
            }

            if ($parentSlug && $selfSlug) {
                return '/' . $parentSlug . '/' . $selfSlug;
            }

            return $selfSlug ? '/' . $selfSlug : '#';

            case 'article':
            $article = $this->resolveArticle();
            if (!$article) {
                return $parentSlug && $selfSlug ? '/' . $parentSlug . '/' . $selfSlug : ($selfSlug ? '/' . $selfSlug : '#');
            }

            $articleSlug = $article->slug ?: $article->alias ?: $selfSlug;

            if ($parentSlug) {
                return '/' . $parentSlug . '/' . $articleSlug;
            }

            return '/' . $articleSlug;

            case 'category':
            $category = $this->resolveCategory();
            if (!$category) {
                return $parentSlug && $selfSlug ? '/' . $parentSlug . '/' . $selfSlug : ($selfSlug ? '/' . $selfSlug : '#');
            }

            $categorySlug = $category->slug ?: (property_exists($category, 'alias') ? $category->alias : null) ?: $selfSlug;
            $pageName = trim((string) $this->page_name);
            $pageParam = trim((string) ($this->page_param ?: 'slug'));

            if ($categorySlug === 'download-gratuito' && class_exists(Page::class)) {
                try {
                    $downloadsPage = Page::loadCached($this->getThemeName(), 'downloads');
                    if ($downloadsPage) {
                        $url = $downloadsPage->url();
                        if (is_string($url) && trim($url) !== '') {
                            return $url;
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            if ($pageName !== '') {
                $pageUrl = $this->buildCmsPageUrl($pageName, [
                    'cat' => $parentSlug ?: $categorySlug,
                    'categoria' => $categorySlug,
                    'slug' => $categorySlug,
                    $pageParam => $categorySlug,
                ]);

                if ($pageUrl !== '') {
                    return $pageUrl;
                }
            }

            if ($parentSlug) {
                return '/' . $parentSlug . '/' . $categorySlug;
            }

            return '/' . $categorySlug;

            case 'plugin':
            return $this->buildDynamicFrontendUrl($parentSlug, $selfSlug);

            case 'url':
            return $this->url ?: '#';
        }

        return '#';
    }

    public function beforeSave()
    {
        if ($this->type !== 'article') {
            $this->article_id = null;
        }

        if ($this->type !== 'category') {
            $this->category_id = null;
        }

        if ($this->type !== 'plugin') {
            $this->dynamic_source = null;
            $this->dynamic_record_id = null;
        }

        if ($this->type !== 'url') {
            $this->url = null;
        }
    }

    protected function buildDynamicFrontendUrl(?string $parentSlug, string $selfSlug): string
    {
        $parentSlug = trim((string) $parentSlug, '/');
        $selfSlug   = trim((string) $selfSlug, '/');

        $fallbackUrl = '#';

        if ($parentSlug !== '' && $selfSlug !== '') {
            $fallbackUrl = '/' . $parentSlug . '/' . $selfSlug;
        } elseif ($selfSlug !== '') {
            $fallbackUrl = '/' . $selfSlug;
        }

        $sourceKey = trim((string) $this->dynamic_source);
        if ($sourceKey === '') {
            return $fallbackUrl;
        }

        $source = $this->getDynamicSourceConfig($sourceKey);
        if (!is_array($source) || empty($source)) {
            return $fallbackUrl;
        }

        $record = $this->resolveDynamicRecord();
        if (!$record) {
            return $fallbackUrl;
        }

        $urlColumn = trim((string) ($source['urlColumn'] ?? ''));
        if ($urlColumn !== '') {
            $urlValue = trim((string) data_get($record, $urlColumn, ''));

            if ($urlValue !== '') {
                return $this->normalizeDynamicUrl($urlValue, (bool) ($source['isMediaPath'] ?? false));
            }
        }

        $slugColumn  = trim((string) ($source['slugColumn'] ?? 'slug'));
        $pageParam   = trim((string) ($source['pageParam'] ?? 'slug'));
        $routePrefix = trim((string) ($source['routePrefix'] ?? ''), '/');
        $pageName    = trim((string) ($this->page_name ?: ($source['pageName'] ?? '')));
        if (!empty($source['useMenuSlug'])) {
            $slugValue = $selfSlug;
        } else {
            $slugValue = trim((string) data_get($record, $slugColumn, ''));
        }

        $anchorColumn = trim((string) ($source['anchorColumn'] ?? ''));
        if ($pageName !== '' && $anchorColumn !== '' && class_exists(Page::class)) {
            $anchorValue = trim((string) data_get($record, $anchorColumn, ''));

            try {
                $page = Page::loadCached($this->getThemeName(), $pageName);

                if ($page && $anchorValue !== '') {
                    $url = $page->url();

                    if (is_string($url) && trim($url) !== '') {
                        $anchorPrefix = (string) ($source["anchorPrefix"] ?? "");
                        return rtrim($url, "/") . "#" . $anchorPrefix . Str::slug($anchorValue);
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($pageName !== '' && $slugValue !== '' && class_exists(Page::class)) {
            try {
                $page = Page::loadCached($this->getThemeName(), $pageName);

                if ($page) {
                    $url = $page->url([$pageParam => $slugValue]);

                    if (is_string($url) && trim($url) !== '') {
                        return $url;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($routePrefix !== '' && $slugValue !== '') {
            return '/' . $routePrefix . '/' . $slugValue;
        }

        return $fallbackUrl;
    }

    protected function buildCmsPageUrl(string $pageName, array $params = []): string
    {
        $pageName = trim($pageName);

        if ($pageName === '' || !class_exists(Page::class)) {
            return '';
        }

        try {
            $page = Page::loadCached($this->getThemeName(), $pageName);

            if (!$page) {
                return '';
            }

            $cleanParams = [];
            foreach ($params as $key => $value) {
                $key = trim((string) $key);
                $value = trim((string) $value);

                if ($key !== '' && $value !== '') {
                    $cleanParams[$key] = $value;
                }
            }

            $url = $page->url($cleanParams);

            return is_string($url) ? trim($url) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function normalizeDynamicUrl(string $url, bool $isMediaPath = false): string
    {
        $url = trim($url);

        if ($url === '') {
            return '#';
        }

        if (preg_match('#^(https?:)?//#i', $url) || preg_match('#^(mailto:|tel:|#)#i', $url)) {
            return $url;
        }

        if (!$isMediaPath) {
            return '/' . ltrim($url, '/');
        }

        if (strpos($url, '/storage/app/media/') === 0 || strpos($url, 'storage/app/media/') === 0) {
            return '/' . ltrim($url, '/');
        }

        return '/storage/app/media/' . ltrim($url, '/');
    }

    protected function resolveArticle(): ?Article
    {
        if ($this->relationLoaded('article') && $this->article) {
            return $this->article;
        }

        if ($this->article) {
            return $this->article;
        }

        if (!$this->article_id) {
            return null;
        }

        return Article::query()
        ->where('id', $this->article_id)
        ->orWhere('joomla_id', $this->article_id)
        ->first();
    }

    protected function resolveCategory(): ?ArticleCategory
    {
        if ($this->relationLoaded('category') && $this->category) {
            return $this->category;
        }

        if ($this->category) {
            return $this->category;
        }

        if (!$this->category_id) {
            return null;
        }

        return ArticleCategory::query()
        ->where('id', $this->category_id)
        ->orWhere('legacy_id', $this->category_id)
        ->first();
    }

    protected function resolveDynamicRecord(): ?object
    {
        $source = $this->getDynamicSourceConfig($this->dynamic_source);
        if (!$source || empty($this->dynamic_record_id)) {
            return null;
        }

        $modelClass = $source['model'] ?? null;
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        return $modelClass::query()->find($this->dynamic_record_id);
    }

    protected function getDynamicSourceConfig(?string $sourceKey): ?array
    {
        if (!$sourceKey) {
            return null;
        }

        $sources = $this->getDynamicSourcesConfig();
        return $sources[$sourceKey] ?? null;
    }

    protected function getDynamicSourcesConfig(): array
    {
        $config = @include plugins_path('siamfesp/menu/config/linkables.php');
        return is_array($config) ? $config : [];
    }

    protected function getThemeName(): string
    {
        $activeTheme = \Cms\Classes\Theme::getActiveTheme();

        return $activeTheme ? $activeTheme->getDirName() : 'siamfesp';
    }

    protected function hasColumn(string $modelClass, string $column): bool
    {
        $instance = new $modelClass;

        return \Schema::hasColumn($instance->getTable(), $column);
    }
}
