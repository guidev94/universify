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
            // Menu Principal: Menus
            'menu' => [
                'label'       => 'Menus',
                'url'         => Backend::url('siamfesp/menu/menus'),
                'icon'        => 'icon-sitemap',
                'permissions' => ['siamfesp.menu.manage'], // Ajustado para a permissão específica cadastrada abaixo
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
            
            // Novo Menu Principal Separado: Logins do Site
            'siteusers' => [
                'label'       => 'Logins do Site',
                'url'         => Backend::url('siamfesp/menu/siteusers'),
                'icon'        => 'icon-lock',
                'permissions' => ['siamfesp.menu.manage'],
                'order'       => 510, // Fica logo após o menu de Menus
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