<?php namespace Siamfesp\Newsletter;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Newsletter',
            'description' => 'Cadastro de inscritos da newsletter e envio de notificação por e-mail.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-envelope-o',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.newsletter.manage_subscribers' => [
                'tab' => 'Newsletter',
                'label' => 'Gerenciar inscritos da newsletter',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'newsletter' => [
                'label'       => 'Newsletter',
                'url'         => Backend::url('siamfesp/newsletter/subscribers'),
                'icon'        => 'icon-envelope-o',
                'permissions' => ['siamfesp.newsletter.*'],
                'order'       => 335,
            ],
        ];
    }

    public function registerComponents()
    {
        return [
            'Siamfesp\\Newsletter\\Components\\NewsletterForm' => 'newsletterForm',
        ];
    }
}
