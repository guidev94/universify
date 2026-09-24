<?php namespace Siamfesp\Menu\Models;

use Cms\Classes\Page;
use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Artigos\Models\Article;
use Siamfesp\Artigos\Models\ArticleCategory;
use Str;
use Siamfesp\Menu\Traits\AutoSlug;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class MenuItem extends Model
{
    use Validation;
    use AutoSlug;
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
        'requires_login',
        'is_published',
        'sort_order',
    ];

    protected $appends = ['frontend_url', 'target_url', 'should_open_blank', 'is_visible'];

    protected $casts = [
        'target_blank' => 'boolean',
        'requires_login' => 'boolean',
        'is_published' => 'boolean',
    ];

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
        return $this->buildFrontendUrl(true);
    }

    public function getTargetUrlAttribute(): string
    {
        return $this->buildFrontendUrl(false);
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

    public function getIsVisibleAttribute(): bool
    {
        // Itens restritos continuam aparecendo no menu.
        // Quando o visitante clica, o link é direcionado para /login?redirect=...
        return true;
    }

    public static function applyVisibilityToQuery($query)
    {
        // Mantido por compatibilidade com versões antigas do template.
        // Não esconde mais os itens restritos.
        return $query;
    }

    public function requiresLoginIncludingParents(): bool
    {
        if ((bool) $this->requires_login) {
            return true;
        }

        try {
            $parent = $this->parent ?: null;
        } catch (\Throwable $e) {
            $parent = null;
        }

        while ($parent) {
            if ((bool) $parent->requires_login) {
                return true;
            }

            try {
                $parent = $parent->parent ?: null;
            } catch (\Throwable $e) {
                $parent = null;
            }
        }

        return false;
    }

    public static function isBackendRequestPath(?string $path = null): bool
    {
        try {
            $path = $path ?: \Request::getRequestUri();
        } catch (\Throwable $e) {
            $path = $path ?: '';
        }

        $requestPath = parse_url((string) $path, PHP_URL_PATH) ?: '/';
        $requestPath = '/' . trim($requestPath, '/');
        $requestPath = $requestPath === '/' ? '/' : rtrim($requestPath, '/');

        $backendUri = 'admin';
        try {
            if (class_exists('Backend\\Facades\\Backend') && method_exists('Backend\\Facades\\Backend', 'uri')) {
                $backendUri = trim((string) \Backend\Facades\Backend::uri(), '/');
            }
        } catch (\Throwable $e) {
            $backendUri = 'admin';
        }

        $prefixes = array_values(array_unique(array_filter([
            '/admin',
            '/' . trim($backendUri ?: 'admin', '/'),
            '/backend',
        ])));

        foreach ($prefixes as $prefix) {
            $prefix = '/' . trim($prefix, '/');
            if ($requestPath === $prefix || strpos($requestPath, $prefix . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    public static function isFrontendUserLoggedIn(): bool
    {
        /*
         * O login do site é separado do /admin.
         * Nunca use BackendAuth, Laravel Auth genérico, nem chaves genéricas como
         * "user"/"user_id" para liberar conteúdo restrito do frontend.
         */
        if (self::isBackendRequestPath()) {
            return false;
        }

        try {
            if (function_exists('session')) {
                $session = session();

                foreach ([
                    'siamfesp_site_auth',
                    'siamfesp_site_logged_in',
                    'siamfesp_frontend_auth',
                    'siamfesp_frontend_user',
                    'siamfesp_frontend_user_id',
                    'siamfesp_user',
                    'siamfesp_user_id',
                    'siamfesp_associado',
                    'siamfesp_associado_id',
                    'cliente_frontend_id',
                ] as $key) {
                    if ($session->has($key) && !empty($session->get($key))) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            // RainLab.User é login de frontend, diferente do backend do OctoberCMS.
            if (class_exists('RainLab\\User\\Facades\\Auth')) {
                $frontendUser = \RainLab\User\Facades\Auth::getUser();

                if ($frontendUser && !is_a($frontendUser, 'Backend\\Models\\User')) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }


    public static function makeLoginRedirectUrl(string $targetUrl): string
    {
        $targetUrl = trim($targetUrl);

        if ($targetUrl === '' || $targetUrl === '#') {
            $targetUrl = '/';
        }

        // Evita redirecionamento aberto. Para menu externo, cai na home após login.
        if (preg_match('#^https?://#i', $targetUrl)) {
            $host = parse_url($targetUrl, PHP_URL_HOST);
            $currentHost = null;

            try {
                $currentHost = request()->getHost();
            } catch (\Throwable $e) {
            }

            if (!$currentHost || strtolower((string) $host) !== strtolower((string) $currentHost)) {
                $targetUrl = '/';
            } else {
                $path = parse_url($targetUrl, PHP_URL_PATH) ?: '/';
                $query = parse_url($targetUrl, PHP_URL_QUERY);
                $targetUrl = $path . ($query ? '?' . $query : '');
            }
        }

        if (strpos($targetUrl, '/') !== 0) {
            $targetUrl = '/' . $targetUrl;
        }

        return '/login?redirect=' . rawurlencode($targetUrl);
    }

    public static function pathRequiresLogin(string $path): bool
    {
        if (!\Schema::hasTable('siamfesp_menu_items') || !\Schema::hasColumn('siamfesp_menu_items', 'requires_login')) {
            return false;
        }

        $path = self::normalizePathForCompare($path);

        if ($path === '/login' || $path === '/' || self::isBackendRequestPath($path)) {
            return false;
        }

        try {
            $items = self::query()
                ->where('is_published', 1)
                ->with(['parent.parent.parent', 'article.category', 'category'])
                ->get();

            foreach ($items as $item) {
                if (!$item->requiresLoginIncludingParents()) {
                    continue;
                }

                foreach (self::getRestrictedPathCandidatesForItem($item) as $target) {
                    if ($target === '/' || $target === '#') {
                        continue;
                    }

                    if ($path === $target || strpos($path, $target . '/') === 0) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }


    /**
     * Compatibilidade para páginas CMS antigas em cache que ainda chamam este método.
     * O bloqueio principal já é feito por pathRequiresLogin(), mas este método evita
     * erro fatal enquanto o cache/opcache do servidor não é limpo.
     */
    public static function isRestrictedServicesFallbackPath(string $path): bool
    {
        if (!\Schema::hasTable('siamfesp_menu_items') || !\Schema::hasColumn('siamfesp_menu_items', 'requires_login')) {
            return false;
        }

        $path = self::normalizePathForCompare($path);

        if ($path === '/login' || $path === '/' || self::isBackendRequestPath($path)) {
            return false;
        }

        if (strpos($path, '/servicos/') !== 0) {
            return false;
        }

        $pathsToTest = array_values(array_unique(array_filter([
            $path,
            self::makePdfEquivalentPath($path),
            self::makeServiceNonPdfEquivalentPath($path),
        ])));

        try {
            $items = self::query()
                ->where('is_published', 1)
                ->with(['parent.parent.parent', 'article.category', 'category'])
                ->get();

            foreach ($items as $item) {
                if (!$item->requiresLoginIncludingParents()) {
                    continue;
                }

                foreach (self::getRestrictedPathCandidatesForItem($item) as $target) {
                    $target = self::normalizePathForCompare((string) $target);

                    if ($target === '/' || $target === '#') {
                        continue;
                    }

                    foreach ($pathsToTest as $candidate) {
                        if ($candidate === $target || strpos($candidate, $target . '/') === 0) {
                            return true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }


    protected static function makePdfEquivalentPath(string $target): string
    {
        $target = self::normalizePathForCompare($target);

        if (strpos($target, '/servicos/') === 0 && strpos($target, '/servicos/pdf/') !== 0) {
            return '/servicos/pdf/' . ltrim(substr($target, strlen('/servicos/')), '/');
        }

        return $target;
    }

    protected static function getRestrictedPathCandidatesForItem(self $item): array
    {
        $candidates = [];

        try {
            $candidates[] = $item->buildFrontendUrl(false);
        } catch (\Throwable $e) {
        }

        $selfSlug = trim((string) ($item->slug ?: Str::slug($item->title)), '/');
        $ancestorSlugs = self::getAncestorSlugs($item);
        $parentSlug = end($ancestorSlugs) ?: '';
        $ancestorPath = implode('/', $ancestorSlugs);

        if ($selfSlug !== '') {
            $candidates[] = '/' . $selfSlug;

            if ($parentSlug !== '') {
                $candidates[] = '/' . $parentSlug . '/' . $selfSlug;
            }

            if ($ancestorPath !== '') {
                $candidates[] = '/' . $ancestorPath . '/' . $selfSlug;
            }

            // Itens de Serviços podem ser acessados tanto como:
            // /servicos/dados-estatisticos quanto /servicos/pdf/dados-estatisticos.
            // O bloqueio precisa cobrir os dois formatos, mesmo que o menu use apenas um deles.
            if ($parentSlug === 'servicos' || in_array('servicos', $ancestorSlugs, true)) {
                $candidates[] = '/servicos/' . $selfSlug;
                $candidates[] = '/servicos/pdf/' . $selfSlug;
            }
        }

        $expanded = [];
        foreach ($candidates as $candidate) {
            $candidate = self::normalizePathForCompare((string) $candidate);
            if ($candidate === '' || $candidate === '#') {
                continue;
            }

            $expanded[] = $candidate;
            $expanded[] = self::makePdfEquivalentPath($candidate);
            $expanded[] = self::makeServiceNonPdfEquivalentPath($candidate);
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    protected static function getAncestorSlugs(self $item): array
    {
        $slugs = [];

        try {
            $parent = $item->parent ?: null;
        } catch (\Throwable $e) {
            $parent = null;
        }

        while ($parent) {
            $slug = trim((string) ($parent->slug ?: Str::slug($parent->title)), '/');

            if ($slug !== '') {
                array_unshift($slugs, $slug);
            }

            try {
                $parent = $parent->parent ?: null;
            } catch (\Throwable $e) {
                $parent = null;
            }
        }

        return $slugs;
    }

    protected static function makeServiceNonPdfEquivalentPath(string $target): string
    {
        $target = self::normalizePathForCompare($target);

        if (strpos($target, '/servicos/pdf/') === 0) {
            return '/servicos/' . ltrim(substr($target, strlen('/servicos/pdf/')), '/');
        }

        return $target;
    }

    protected static function normalizePathForCompare(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function buildFrontendUrl(bool $applyRestriction = true): string
    {
        $url = $this->buildFrontendUrlRaw();

        if ($applyRestriction && $this->requiresLoginIncludingParents() && !self::isFrontendUserLoggedIn()) {
            return self::makeLoginRedirectUrl($url);
        }

        return $url;
    }

    public function buildFrontendUrlRaw(): string
    {
        $parentSlug = $this->parent ? trim((string) ($this->parent->slug ?: Str::slug($this->parent->title)), '/') : null;
        $selfSlug   = trim((string) ($this->slug ?: Str::slug($this->title)), '/');
        $menuSlug   = '';

        try {
            if ($this->menu) {
                $menuSlug = trim((string) ($this->menu->slug ?? ''), '/');
            }
        } catch (\Throwable $e) {
            $menuSlug = '';
        }

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
                if ($parentSlug && $selfSlug) {
                    return '/' . $parentSlug . '/' . $selfSlug;
                }

                if ($menuSlug !== '' && $menuSlug !== 'principal' && $selfSlug) {
                    return '/' . $menuSlug . '/' . $selfSlug;
                }

                return $selfSlug ? '/' . $selfSlug : '#';
            }

            $articleSlug = trim((string) ($article->slug ?: $article->alias ?: $selfSlug), '/');
            // Para links vindos do administrador de menu, a URL pública deve respeitar
            // o slug cadastrado no item do menu. Ex.: item "Acordos Coletivos"
            // apontando para o artigo/base "Trabalhista" deve gerar
            // /destaque/acordos-coletivos, não /destaque/trabalhista.
            $routeSlug = $selfSlug ?: $articleSlug;
            $articleCategorySlug = '';

            try {
                if ($article->category) {
                    $articleCategorySlug = trim((string) ($article->category->slug ?? ''), '/');
                }
            } catch (\Throwable $e) {
                $articleCategorySlug = '';
            }

            $pageName = trim((string) $this->page_name);
            $pageParam = trim((string) ($this->page_param ?: 'slug'));
            $routeCatSlug = $parentSlug
                ?: (($menuSlug !== '' && $menuSlug !== 'principal')
                    ? $menuSlug
                    : ($articleCategorySlug ?: $selfSlug));

            // A seleção "Página CMS" no menu deve controlar o modelo também para itens do tipo Artigo.
            // Quando o item pertence a um menu como "Destaque" e não tem item pai,
            // usamos o slug do próprio menu como primeiro parâmetro da rota: /destaque/slug-do-artigo.
            if ($pageName !== '') {
                $pageUrl = $this->buildCmsPageUrl($pageName, [
                    'cat' => $routeCatSlug,
                    'categoria' => $articleCategorySlug ?: ($routeCatSlug ?: $selfSlug),
                    'slug' => $routeSlug,
                    'article' => $articleSlug,
                    $pageParam => $routeSlug,
                ]);

                if ($pageUrl !== '') {
                    return $pageUrl;
                }
            }

            if ($routeCatSlug) {
                return '/' . $routeCatSlug . '/' . $routeSlug;
            }

            return '/' . $routeSlug;

            case 'category':
            $category = $this->resolveCategory();
            if (!$category) {
                return $parentSlug && $selfSlug ? '/' . $parentSlug . '/' . $selfSlug : ($selfSlug ? '/' . $selfSlug : '#');
            }

            $categorySlug = $category->slug ?: (property_exists($category, 'alias') ? $category->alias : null) ?: $selfSlug;

            if (in_array($menuSlug, ['novas-parcerias', 'novas-parceirias', 'nossas-parcerias'], true)) {
                $routeSlug = $selfSlug ?: $categorySlug;
                return '/novas-parceirias/' . trim($routeSlug, '/');
            }

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

    public function beforeValidate()
    {
        $this->title = trim((string) $this->title);
        $this->ensureAutomaticSlug('slug', 'title');
    }

    public function beforeSave()
    {
        $this->ensureAutomaticSlug('slug', 'title');

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

        /*
         * Primeiro procura pelo ID local do October. Só usa joomla_id como fallback.
         * Isso evita conflito quando um artigo local tem id igual ao joomla_id de outro.
         */
        $article = Article::query()
            ->where('id', $this->article_id)
            ->first();

        if ($article) {
            return $article;
        }

        if ($this->hasColumn(Article::class, 'joomla_id')) {
            return Article::query()
                ->where('joomla_id', $this->article_id)
                ->first();
        }

        return null;
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
