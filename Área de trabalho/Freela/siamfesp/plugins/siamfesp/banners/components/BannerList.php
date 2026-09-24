<?php namespace Siamfesp\Banners\Components;

use Cms\Classes\ComponentBase;
use Siamfesp\Banners\Models\Banner;

class BannerList extends ComponentBase
{
    public $banners;

    public function componentDetails()
    {
        return [
            'name'        => 'Lista de Banners',
            'description' => 'Exibe os banners publicados cadastrados no plugin Banners.',
        ];
    }

    public function defineProperties()
    {
        return [
            'limit' => [
                'title'             => 'Quantidade máxima',
                'description'       => 'Limite de banners exibidos. Use 0 para exibir todos.',
                'default'           => 0,
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Informe apenas números.',
            ],
        ];
    }

    public function onRun()
    {
        $this->banners = $this->page['banners'] = $this->loadBanners();
        $this->page['allBannersImageOnly'] = $this->allBannersAreImageOnly($this->banners);
    }

    protected function allBannersAreImageOnly($banners): bool
    {
        if (!$banners || !$banners->count()) {
            return false;
        }

        foreach ($banners as $banner) {
            if (!(bool) $banner->is_image_only) {
                return false;
            }
        }

        return true;
    }

    protected function loadBanners()
    {
        $query = Banner::published()->ordered();
        $limit = (int) $this->property('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
