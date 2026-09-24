<?php namespace Siamfesp\Parcerias\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Siamfesp\Menu\Traits\AutoSlug;
use System\Models\File as SystemFile;

if (!trait_exists('Siamfesp\\Menu\\Traits\\AutoSlug', false)) {
    require_once plugins_path('siamfesp/menu/traits/AutoSlug.php');
}

class Partner extends Model
{
    use Validation;
    use AutoSlug;
    use Sortable;

    public $table = 'siamfesp_parcerias_partners';
    protected $guarded = [];
    protected $fillable = [
        'type', 'title', 'slug', 'badge', 'image', 'description', 'link_url',
        'link_target', 'is_featured', 'is_published', 'sort_order'
    ];
    protected $appends = ['image_url', 'frontend_url', 'short_description'];
    public $sortable = 'sort_order';

    public $attachOne = [
        'image_file' => ['System\Models\File', 'delete' => true],
    ];

    public $rules = [
        'type' => 'required',
        'title' => 'required',
        'slug' => 'required',
    ];

    public $customMessages = [
        'type.required' => 'Selecione o tipo da parceria.',
        'title.required' => 'Informe o nome do parceiro.',
        'slug.required' => 'Informe o slug do parceiro.',
    ];

    public function getTypeOptions(): array
    {
        return [
            'siamfesp' => 'Parcerias SIAMFESP',
            'fiesp' => 'Parcerias FIESP',
        ];
    }

    public function getLinkTargetOptions(): array
    {
        return [
            '_self' => 'Abrir na mesma aba',
            '_blank' => 'Abrir em nova aba',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function beforeValidate()
    {
        $this->normalizeBasicFields();
        $this->ensureAutomaticSlug('slug', 'title', ['scopeColumns' => ['type']]);
    }

    public function beforeSave()
    {
        $this->normalizeBasicFields();
        $this->ensureAutomaticSlug('slug', 'title', ['scopeColumns' => ['type']]);
        $this->description = trim((string) $this->description) ?: null;
        $this->link_url = trim((string) $this->link_url) ?: null;
        $this->image = $this->normalizeImagePath($this->image);
        $this->is_published = (bool) $this->is_published;
        $this->is_featured = (bool) $this->is_featured;
    }

    public function afterSave()
    {
        // Se alguém preencher um caminho interno no campo antigo, tenta converter para upload.
        $this->migrateLocalImageToUpload(false);
    }

    protected function normalizeBasicFields(): void
    {
        $this->type = in_array($this->type, ['siamfesp', 'fiesp'], true) ? $this->type : 'siamfesp';
        $this->title = trim((string) $this->title);
        $this->slug = trim((string) $this->slug);
        $this->badge = trim((string) $this->badge) ?: ($this->type === 'fiesp' ? 'Parceiro FIESP' : 'Parceiro SIAMFESP');
        $this->link_target = in_array($this->link_target, ['_self', '_blank'], true) ? $this->link_target : '_self';
    }

    public function getImageUrlAttribute(): string
    {
        try {
            if ($this->image_file) {
                return $this->image_file->getPath();
            }
        } catch (\Exception $ex) {
            // Mantém compatibilidade com os registros antigos que usam link/caminho em texto.
        }

        $path = trim((string) $this->image);
        if ($path === '') return '';

        if (preg_match('#^(https?:)?//#i', $path)) return $path;
        if (strpos($path, '/') === 0) return $path;
        if (strpos($path, 'storage/app/media/') === 0) return '/' . ltrim($path, '/');

        return url('/storage/app/media/' . ltrim($path, '/'));
    }

    public function getFrontendUrlAttribute(): string
    {
        return $this->type === 'fiesp' ? '/parcerias-fiesp' : '/parcerias-siamfesp';
    }

    public function getShortDescriptionAttribute(): string
    {
        $text = html_entity_decode(strip_tags((string) $this->description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > 160) {
            return rtrim(mb_substr($text, 0, 157, 'UTF-8')) . '...';
        }

        return $text;
    }

    /**
     * Move caminhos internos antigos (/images, storage/app/media etc.) para o upload "Imagem".
     * URLs externas continuam no campo antigo para não perder dados.
     */
    public function migrateLocalImageToUpload(bool $saveModel = true): bool
    {
        $path = trim((string) $this->image);
        if ($path === '') return false;

        // URLs externas devem permanecer no campo antigo.
        if ($this->isExternalImagePath($path)) return false;

        // Se já existe upload, limpa apenas o caminho interno antigo.
        try {
            if ($this->image_file) {
                if ($saveModel) {
                    $this->image = null;
                    $this->forceSaveQuietlyIfPossible();
                }
                else {
                    $this->image = null;
                    $this->saveQuietlyIfPossible();
                }
                return true;
            }
        } catch (\Exception $ex) {}

        $absolutePath = $this->resolveLocalImageAbsolutePath($path);
        if (!$absolutePath || !is_file($absolutePath) || !is_readable($absolutePath)) {
            // Não apaga o texto se o arquivo não for encontrado, para não perder referência.
            return false;
        }

        try {
            $file = new SystemFile();
            $file->is_public = true;

            if (method_exists($file, 'fromFile')) {
                $file->fromFile($absolutePath);
            }
            else {
                $file->data = $absolutePath;
            }

            $file->save();
            $this->image_file()->add($file);
            $this->image = null;

            if ($saveModel) {
                $this->forceSaveQuietlyIfPossible();
            }
            else {
                $this->saveQuietlyIfPossible();
            }

            return true;
        }
        catch (\Exception $ex) {
            return false;
        }
    }

    public function isExternalImagePath(?string $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') return false;

        return (bool) preg_match('#^(https?:)?//#i', $value);
    }

    protected function normalizeImagePath($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        // URLs externas continuam intactas.
        if ($this->isExternalImagePath($value)) return $value;

        // Caminhos internos do site continuam legíveis até serem migrados para o upload.
        if (strpos($value, '/images/') === 0) return $value;
        if (strpos($value, 'images/') === 0) return '/' . ltrim($value, '/');
        if (strpos($value, '/storage/app/media/') === 0) return $value;
        if (strpos($value, 'storage/app/media/') === 0) return '/' . ltrim($value, '/');

        // Caminhos absolutos já salvos continuam como estão.
        if (strpos($value, '/') === 0) return $value;

        return ltrim($value, '/');
    }

    protected function resolveLocalImageAbsolutePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') return null;

        $urlPath = parse_url($path, PHP_URL_PATH);
        $path = $urlPath ? $urlPath : $path;
        $path = rawurldecode($path);
        $relative = ltrim($path, '/');

        $candidates = [];

        if (function_exists('base_path')) {
            $candidates[] = base_path($relative);
            $candidates[] = base_path('public/' . $relative);
        }

        if (function_exists('public_path')) {
            $candidates[] = public_path($relative);
        }

        if (function_exists('storage_path')) {
            if (strpos($relative, 'storage/app/media/') === 0) {
                $candidates[] = storage_path('app/media/' . substr($relative, strlen('storage/app/media/')));
            }
            else {
                $candidates[] = storage_path('app/media/' . $relative);
            }
        }

        if (strpos($relative, 'storage/app/media/') === 0 && function_exists('base_path')) {
            $candidates[] = base_path($relative);
        }
        elseif (function_exists('base_path')) {
            $candidates[] = base_path('storage/app/media/' . $relative);
        }

        foreach (array_unique(array_filter($candidates)) as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function forceSaveQuietlyIfPossible(): void
    {
        if (method_exists($this, 'forceSave')) {
            $this->forceSave();
            return;
        }

        $this->saveQuietlyIfPossible();
    }

    protected function saveQuietlyIfPossible(): void
    {
        if (method_exists($this, 'saveQuietly')) {
            $this->saveQuietly();
            return;
        }

        static $savingQuietly = false;
        if ($savingQuietly) return;

        $savingQuietly = true;
        try {
            $this->save();
        }
        finally {
            $savingQuietly = false;
        }
    }
}
