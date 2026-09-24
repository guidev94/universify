<?php namespace Siamfesp\Banners;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Banners',
            'description' => 'Cadastro de banners da home e banners internos do site.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-image',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.banners.manage_banners' => [
                'tab'   => 'Banners',
                'label' => 'Gerenciar Banners da Home',
            ],
            'siamfesp.banners.manage_internal_banners' => [
                'tab'   => 'Banners',
                'label' => 'Gerenciar Banners Internos',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'banners' => [
                'label'       => 'Banners',
                'url'         => Backend::url('siamfesp/banners/banners'),
                'icon'        => 'icon-image',
                'permissions' => ['siamfesp.banners.*'],
                'order'       => 320,
                'sideMenu'    => [
                    'homebanners' => [
                        'label'       => 'Banners da Home',
                        'icon'        => 'icon-picture-o',
                        'url'         => Backend::url('siamfesp/banners/banners'),
                        'permissions' => ['siamfesp.banners.manage_banners'],
                    ],
                    'internalbanners' => [
                        'label'       => 'Banners Internos',
                        'icon'        => 'icon-image',
                        'url'         => Backend::url('siamfesp/banners/internalbanners'),
                        'permissions' => ['siamfesp.banners.manage_internal_banners'],
                    ],
                ],
            ],
        ];
    }

    public function registerComponents()
    {
        return [
            \Siamfesp\Banners\Components\BannerList::class => 'bannerList',
            \Siamfesp\Banners\Components\InternalBanner::class => 'internalBanner',
        ];
    }
}
