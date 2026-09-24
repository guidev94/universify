<?php namespace Siamfesp\Lgpd;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'LGPD',
            'description' => 'Política de privacidade, aviso de cookies e registro de consentimentos.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-shield',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.lgpd.manage_consents' => [
                'tab' => 'LGPD',
                'label' => 'Gerenciar consentimentos de cookies',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'lgpd' => [
                'label'       => 'LGPD',
                'url'         => Backend::url('siamfesp/lgpd/consents'),
                'icon'        => 'icon-shield',
                'permissions' => ['siamfesp.lgpd.*'],
                'order'       => 336,
            ],
        ];
    }

    public function registerComponents()
    {
        return [
            'Siamfesp\\Lgpd\\Components\\CookieConsent' => 'lgpdCookie',
        ];
    }
}
