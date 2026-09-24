<?php namespace Siamfesp\Artigos;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Artigos',
            'description' => 'Cadastro de categorias e artigos com migração do Joomla',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-newspaper-o'
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.artigos.manage_article_categories' => [
                'tab' => 'Artigos',
                'label' => 'Gerenciar Categorias de Artigos'
            ],
            'siamfesp.artigos.manage_articles' => [
                'tab' => 'Artigos',
                'label' => 'Gerenciar Artigos'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'artigos' => [
                'label'       => 'Artigos',
                'url'         => Backend::url('siamfesp/artigos/articles'),
                'icon'        => 'icon-newspaper-o',
                'permissions' => ['siamfesp.artigos.*'],
                'order'       => 310,
                'sideMenu' => [
                    'articlecategories' => [
                        'label'       => 'Categorias',
                        'icon'        => 'icon-sitemap',
                        'url'         => Backend::url('siamfesp/artigos/articlecategories'),
                        'permissions' => ['siamfesp.artigos.manage_article_categories'],
                    ],
                    'articles' => [
                        'label'       => 'Artigos',
                        'icon'        => 'icon-newspaper-o',
                        'url'         => Backend::url('siamfesp/artigos/articles'),
                        'permissions' => ['siamfesp.artigos.manage_articles'],
                    ],
                ],
            ],
        ];
    }
}
