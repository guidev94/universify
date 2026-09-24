<?php namespace Siamfesp\Agenda;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Agenda',
            'description' => 'Cadastro de eventos da agenda do setor.',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-calendar',
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.agenda.manage_events' => [
                'tab' => 'Agenda',
                'label' => 'Gerenciar eventos da agenda',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'agenda' => [
                'label'       => 'Agenda',
                'url'         => Backend::url('siamfesp/agenda/events'),
                'icon'        => 'icon-calendar',
                'permissions' => ['siamfesp.agenda.*'],
                'order'       => 330,
            ],
        ];
    }
}
