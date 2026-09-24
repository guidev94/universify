<?php namespace Siamfesp\Menu;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Menu',
            'description' => 'Gerenciamento de menus e itens de menu.',
            'author'      => 'OpenAI',
            'icon'        => 'icon-sitemap'
        ];
    }

    public function registerNavigation()
    {
        return [
            'menu' => [
                'label'       => 'Menus',
                'url'         => Backend::url('siamfesp/menu/menus'),
                'icon'        => 'icon-sitemap',
                'permissions' => ['siamfesp.menu.*'],
                'order'       => 500,
                'sideMenu'    => [
                    'menus' => [
                        'label' => 'Menus',
                        'icon'  => 'icon-bars',
                        'url'   => Backend::url('siamfesp/menu/menus'),
                    ],
                    'menuitems' => [
                        'label' => 'Itens do Menu',
                        'icon'  => 'icon-list-ul',
                        'url'   => Backend::url('siamfesp/menu/menuitems'),
                    ],
                ],
            ],
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.menu.manage' => [
                'tab' => 'Menu',
                'label' => 'Gerenciar menus'
            ],
        ];
    }
}
