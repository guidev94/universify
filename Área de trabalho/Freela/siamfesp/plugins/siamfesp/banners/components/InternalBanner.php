<?php namespace Siamfesp\Banners\Components;

use Cms\Classes\ComponentBase;
use Request;
use Siamfesp\Banners\Models\InternalBanner as InternalBannerModel;
use Siamfesp\Menu\Models\MenuItem;

class InternalBanner extends ComponentBase
{
    public $banner;
    public $bannerData;

    public function componentDetails()
    {
        return [
            'name'        => 'Banner Interno',
            'description' => 'Exibe o banner interno vinculado ao item do menu atual, herdando o banner do menu pai quando necessário.',
        ];
    }

    public function defineProperties()
    {
        return [
            'defaultImage' => [
                'title'       => 'Imagem padrão',
                'description' => 'Imagem usada quando não houver banner interno cadastrado para o menu atual.',
                'default'     => 'https://www.siamfesp.org.br/images/banners/banner-servicos2.jpg',
                'type'        => 'string',
            ],
            'forceMenuItemId' => [
                'title'             => 'Forçar item do menu',
                'description'       => 'Opcional. Informe o ID do item de menu para usar sempre o banner desse item.',
                'default'           => '',
                'type'              => 'string',
                'validationPattern' => '^[0-9]*$',
                'validationMessage' => 'Informe apenas números.',
            ],
        ];
    }

    public function onRun()
    {
        $menuItem = $this->resolveMenuItem();
        $this->banner = $this->page['internalBanner'] = $this->resolveBanner($menuItem);
        $this->bannerData = $this->page['internalBannerData'] = $this->buildBannerData($this->banner, $menuItem);
    }

    protected function buildBannerData(?InternalBannerModel $banner, ?MenuItem $menuItem): array
    {
        $fallbackTitle = trim((string) ($this->page['internalBannerFallbackTitle'] ?? $this->page->title ?? ''));
        $fallbackDescription = trim((string) ($this->page['internalBannerFallbackDescription'] ?? $this->page->meta_description ?? ''));
        $fallbackEyebrow = trim((string) ($this->page['internalBannerFallbackEyebrow'] ?? ''));

        if ($fallbackTitle === '' && $menuItem) {
            $fallbackTitle = (string) $menuItem->title;
        }

        if ($fallbackEyebrow === '' && $menuItem && $menuItem->parent) {
            $fallbackEyebrow = (string) $menuItem->parent->title;
        }

        if ($fallbackEyebrow === '' && $menuItem) {
            $fallbackEyebrow = (string) $menuItem->title;
        }

        $image = $banner ? $banner->image_url : '';
        if ($image === '') {
            $image = trim((string) $this->property('defaultImage'));
        }

        $title = $banner && trim((string) $banner->title) !== '' ? trim((string) $banner->title) : $fallbackTitle;
        $description = $banner && trim((string) $banner->description) !== '' ? trim((string) $banner->description) : $fallbackDescription;
        $eyebrow = $banner && trim((string) $banner->eyebrow) !== '' ? trim((string) $banner->eyebrow) : $fallbackEyebrow;
        $showOverlay = $banner ? (bool) $banner->show_overlay : true;

        return [
            'image' => $image,
            'image_url' => $this->resolveImageUrl($image),
            'eyebrow' => $eyebrow,
            'title' => $title,
            'description' => $description,
            'show_overlay' => $showOverlay,
            'menu_item' => $menuItem,
            'banner' => $banner,
        ];
    }


    protected function resolveImageUrl(?string $image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        // URLs externas e imagens em base64 não devem passar pelo media library do OctoberCMS.
        if (preg_match('#^(https?:)?//#i', $image) || preg_match('#^data:image/#i', $image)) {
            return $image;
        }

        // Compatível com versões diferentes do OctoberCMS.
        foreach ([
            '\\System\\Classes\\MediaLibrary',
            '\\Cms\\Classes\\MediaLibrary',
        ] as $mediaLibraryClass) {
            if (class_exists($mediaLibraryClass) && method_exists($mediaLibraryClass, 'url')) {
                return $mediaLibraryClass::url($image);
            }
        }

        // Fallback seguro para instalações onde a classe MediaLibrary não existe.
        return url('/storage/app/media/' . ltrim($image, '/'));
    }

    protected function resolveBanner(?MenuItem $menuItem): ?InternalBannerModel
    {
        $candidateIds = [];

        if ($menuItem) {
            $current = $menuItem;

            while ($current) {
                if ($current->id && !in_array((int) $current->id, $candidateIds, true)) {
                    $candidateIds[] = (int) $current->id;
                }

                $current = $current->parent ?: null;
            }
        }

        foreach ($candidateIds as $menuItemId) {
            $banner = InternalBannerModel::published()
                ->where('menu_item_id', $menuItemId)
                ->ordered()
                ->first();

            if ($banner) {
                return $banner;
            }
        }

        return InternalBannerModel::published()
            ->whereNull('menu_item_id')
            ->ordered()
            ->first();
    }

    protected function resolveMenuItem(): ?MenuItem
    {
        if (!class_exists(MenuItem::class)) {
            return null;
        }

        $forcedId = (int) $this->property('forceMenuItemId');
        if ($forcedId > 0) {
            return MenuItem::query()->with('parent.parent')->where('is_published', 1)->find($forcedId);
        }

        $currentPath = $this->normalizePath('/' . Request::path());
        $items = MenuItem::query()
            ->where('is_published', 1)
            ->with('parent.parent')
            ->orderBy('parent_id', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $matchedItem = null;
        $matchedLength = 0;

        foreach ($items as $item) {
            $itemPath = $this->normalizePath($item->frontend_url ?: '');

            if ($itemPath === '') {
                continue;
            }

            if ($itemPath === $currentPath && strlen($itemPath) >= $matchedLength) {
                $matchedItem = $item;
                $matchedLength = strlen($itemPath);
            }
        }

        if ($matchedItem) {
            return $matchedItem;
        }

        $segments = array_values(array_filter(explode('/', trim($currentPath, '/'))));
        if (!count($segments)) {
            return null;
        }

        $lastSegment = end($segments);
        if ($lastSegment && $lastSegment !== $segments[0]) {
            $slugMatches = MenuItem::query()
                ->where('is_published', 1)
                ->where('slug', $lastSegment)
                ->with('parent.parent')
                ->orderBy('parent_id', 'desc')
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($slugMatches as $candidate) {
                if ($this->menuItemBelongsToRoot($candidate, $segments[0])) {
                    return $candidate;
                }
            }

            if ($slugMatches->count() === 1) {
                return $slugMatches->first();
            }
        }

        return MenuItem::query()
            ->where('is_published', 1)
            ->where('slug', $segments[0])
            ->with('parent.parent')
            ->orderBy('parent_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->first();
    }

    protected function menuItemBelongsToRoot(MenuItem $item, string $rootSlug): bool
    {
        $rootSlug = trim((string) $rootSlug);
        $current = $item;

        while ($current) {
            if ((string) $current->slug === $rootSlug) {
                return true;
            }

            $current = $current->parent ?: null;
        }

        return false;
    }

    protected function normalizePath(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '' || $path === '#') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            $parts = parse_url($path);
            $path = $parts['path'] ?? '';
        }

        $path = '/' . trim($path, '/');

        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/');
    }
}
