<?php namespace Siamfesp\Banners\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Menu\Models\MenuItem;

class InternalBanner extends Model
{
    use Validation;
    use Sortable;

    public $table = 'siamfesp_internal_banners';

    protected $guarded = [];

    protected $fillable = [
        'menu_item_id',
        'name',
        'eyebrow',
        'title',
        'description',
        'image',
        'show_overlay',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'show_overlay' => 'boolean',
        'is_published' => 'boolean',
    ];

    public $rules = [
        'name' => 'required|max:255',
        'image' => 'required',
        'menu_item_id' => 'nullable|integer',
        'title' => 'nullable|max:255',
        'eyebrow' => 'nullable|max:120',
    ];

    public $belongsTo = [
        'menuItem' => [MenuItem::class, 'key' => 'menu_item_id'],
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

    public function beforeValidate()
    {
        $this->name = trim((string) $this->name);
        $this->eyebrow = trim((string) $this->eyebrow);
        $this->title = trim((string) $this->title);
    }


    public function beforeSave()
    {
        if (!$this->menu_item_id) {
            $this->menu_item_id = null;
        }

        $this->show_overlay = (bool) $this->show_overlay;
        $this->is_published = (bool) $this->is_published;
    }

    public function getMenuItemIdOptions(): array
    {
        if (!class_exists(MenuItem::class)) {
            return [];
        }

        $items = MenuItem::query()
            ->where('is_published', 1)
            ->with('children')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $grouped = $items->groupBy(function ($item) {
            return (int) ($item->parent_id ?: 0);
        });

        $options = [];
        $walk = function ($parentId, $level) use (&$walk, &$options, $grouped) {
            $children = $grouped->get((int) $parentId);

            if (!$children) {
                return;
            }

            foreach ($children as $item) {
                $prefix = str_repeat('— ', max(0, $level));
                $url = $item->frontend_url ?: '#';
                $options[$item->id] = $prefix . $item->title . '  (' . $url . ')';
                $walk($item->id, $level + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }

    public function getMenuItemLabelAttribute(): string
    {
        if ($this->menuItem) {
            $parent = $this->menuItem->parent;
            $prefix = $parent ? ($parent->title . ' > ') : '';

            return $prefix . $this->menuItem->title;
        }

        return 'Banner interno padrão';
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
