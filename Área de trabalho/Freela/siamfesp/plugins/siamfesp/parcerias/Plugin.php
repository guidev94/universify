<?php namespace Siamfesp\Parcerias;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Parcerias',
            'description' => 'Cadastro de parceiros SIAMFESP e FIESP exibidos nas páginas e na home.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-handshake-o',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.parcerias.manage_partners' => [
                'tab' => 'Parcerias',
                'label' => 'Gerenciar parceiros',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'parcerias' => [
                'label'       => 'Parcerias',
                'url'         => Backend::url('siamfesp/parcerias/partners'),
                'icon'        => 'icon-handshake-o',
                'permissions' => ['siamfesp.parcerias.*'],
                'order'       => 325,
            ],
        ];
    }
}
