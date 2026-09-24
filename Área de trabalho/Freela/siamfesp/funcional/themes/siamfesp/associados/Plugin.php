<?php namespace Siamfesp\Associados;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Associados',
            'description' => 'Cadastro de categorias e associados',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-users'
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.associados.manage_categories' => [
                'tab' => 'Associados',
                'label' => 'Gerenciar categorias de associados'
            ],
            'siamfesp.associados.manage_associates' => [
                'tab' => 'Associados',
                'label' => 'Gerenciar associados'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'associados' => [
                'label'       => 'Associados',
                'url'         => Backend::url('siamfesp/associados/associates'),
                'icon'        => 'icon-users',
                'permissions' => ['siamfesp.associados.*'],
                'order'       => 320,
                'sideMenu'    => [
                    'associatecategories' => [
                        'label'       => 'Categorias',
                        'icon'        => 'icon-sitemap',
                        'url'         => Backend::url('siamfesp/associados/associatecategories'),
                        'permissions' => ['siamfesp.associados.manage_categories'],
                    ],
                    'associates' => [
                        'label'       => 'Associados',
                        'icon'        => 'icon-user',
                        'url'         => Backend::url('siamfesp/associados/associates'),
                        'permissions' => ['siamfesp.associados.manage_associates'],
                    ],
                ],
            ],
        ];
    }
}
