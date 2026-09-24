<?php namespace Siamfesp\Videos;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Vídeos',
            'description' => 'Cadastro de vídeos SIAMFESP com upload ou YouTube para exibição no site.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-video-camera',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.videos.manage_videos' => [
                'tab' => 'Vídeos',
                'label' => 'Gerenciar vídeos',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'videos' => [
                'label'       => 'Vídeos',
                'url'         => Backend::url('siamfesp/videos/videos'),
                'icon'        => 'icon-video-camera',
                'permissions' => ['siamfesp.videos.*'],
                'order'       => 330,
            ],
        ];
    }
}
