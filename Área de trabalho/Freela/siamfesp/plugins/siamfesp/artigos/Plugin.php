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

    public function boot()
    {
        $this->registerImageFallbackRoutes();
    }

    protected function registerImageFallbackRoutes(): void
    {
        // As imagens migradas do Joomla ficam na pasta /images da raiz do FTP.
        // Em algumas hospedagens essa pasta não é entregue diretamente pelo Apache/Nginx,
        // então este fallback serve o arquivo pelo OctoberCMS mantendo a URL /images/arquivo.ext.
        \Route::get('images/{path}', function ($path) {
            return self::serveLocalImage($path, [base_path('images'), public_path('images')]);
        })->where('path', '.*');

        // Fallback para imagens do Media Library caso a hospedagem não exponha /storage/app/media.
        \Route::get('storage/app/media/{path}', function ($path) {
            return self::serveLocalImage($path, [storage_path('app/media'), base_path('storage/app/media')]);
        })->where('path', '.*');
    }

    protected static function serveLocalImage($path, array $baseFolders)
    {
        $path = rawurldecode((string) $path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('~^/+~', '', $path);

        if ($path === '' || strpos($path, '..') !== false || !preg_match('~\.(jpe?g|png|gif|webp|svg)$~i', $path)) {
            return response('Imagem inválida.', 404);
        }

        foreach ($baseFolders as $baseFolder) {
            if (!$baseFolder) {
                continue;
            }

            $baseFolder = rtrim(str_replace('\\', '/', $baseFolder), '/');
            $file = $baseFolder . '/' . $path;
            $realBase = realpath($baseFolder);
            $realFile = realpath($file);

            if ($realBase && $realFile && strpos(str_replace('\\', '/', $realFile), str_replace('\\', '/', $realBase) . '/') === 0 && is_file($realFile)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($realFile) : null;
                $headers = [
                    'Content-Type' => $mime ?: self::guessImageMime($realFile),
                    'Cache-Control' => 'public, max-age=31536000',
                ];

                return response()->file($realFile, $headers);
            }
        }

        return response('Imagem não encontrada.', 404);
    }

    protected static function guessImageMime(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            case 'webp':
                return 'image/webp';
            case 'svg':
                return 'image/svg+xml';
            default:
                return 'application/octet-stream';
        }
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
