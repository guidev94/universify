<?php namespace Siamfesp\Pdf;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'PDF',
            'description' => 'Cadastro de temas e upload local de PDFs (Tema + Documento)',
            'author'      => 'Siamfesp',
            'icon'        => 'icon-file-pdf-o'
        ];
    }

    public function registerPermissions()
    {
        return [
            'siamfesp.pdf.manage_themes' => [
                'tab' => 'Pdf',
                'label' => 'Gerenciar Temas'
            ],
            'siamfesp.pdf.manage_documents' => [
                'tab' => 'Pdf',
                'label' => 'Gerenciar Documentos'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'pdf' => [
                'label'       => 'Pdf',
                'url'         => Backend::url('siamfesp/pdf/documents'),
                'icon'        => 'icon-file-pdf-o',
                'permissions' => ['siamfesp.pdf.*'],
                'order'       => 300,

                'sideMenu' => [
                    'documents' => [
                        'label'       => 'Documentos',
                        'icon'        => 'icon-file-text-o',
                        'url'         => Backend::url('siamfesp/pdf/documents'),
                        'permissions' => ['siamfesp.pdf.manage_documents'],
                    ],
                    'themes' => [
                        'label'       => 'Temas',
                        'icon'        => 'icon-tags',
                        'url'         => Backend::url('siamfesp/pdf/themes'),
                        'permissions' => ['siamfesp.pdf.manage_themes'],
                    ],
                ],
            ],
        ];
    }
}
