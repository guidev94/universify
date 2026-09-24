<?php namespace Siamfesp\Menu\Models;

use Model;
use October\Rain\Database\Traits\Validation;

class SiteUser extends Model
{
    use Validation;

    public $table = 'siamfesp_site_users';

    protected $guarded = [];

    protected $fillable = [
        'name',
        'usuario',
        'email',
        'senha',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public $rules = [];

    public function beforeValidate()
    {
        $id = $this->id ?: 'NULL';

        $this->rules = [
            'name'      => 'required',
            'usuario'   => 'required|min:3|unique:siamfesp_site_users,usuario,' . $id,
            'email'     => 'nullable|email|unique:siamfesp_site_users,email,' . $id,
            'senha'     => $this->exists ? 'nullable|min:4' : 'required|min:4',
            'is_active' => 'boolean',
        ];
    }

    public function beforeSave()
    {
        if (!array_key_exists('senha', $this->attributes)) {
            return;
        }

        $senha = trim((string) $this->attributes['senha']);

        // No update, deixar a senha em branco mantém a senha atual.
        if ($senha === '') {
            unset($this->attributes['senha']);
            return;
        }

        // Evita recriptografar hashes já importados manualmente.
        if (self::looksLikeHashValue($senha)) {
            return;
        }

        try {
            if (class_exists('Hash')) {
                $this->attributes['senha'] = \Hash::make($senha);
                return;
            }
        } catch (\Throwable $e) {
        }

        $this->attributes['senha'] = password_hash($senha, PASSWORD_DEFAULT);
    }

    public static function checkPassword($plainPassword, $storedPassword): bool
    {
        $storedPassword = (string) $storedPassword;

        if ($storedPassword === '') {
            return false;
        }

        try {
            if (function_exists('password_verify') && password_verify((string) $plainPassword, $storedPassword)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        try {
            if (class_exists('Hash') && \Hash::check((string) $plainPassword, $storedPassword)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $storedPassword) && hash_equals(strtolower($storedPassword), md5((string) $plainPassword))) {
            return true;
        }

        if (strpos($storedPassword, ':') !== false) {
            [$hash, $salt] = explode(':', $storedPassword, 2);
            if ($hash !== '' && $salt !== '' && hash_equals($hash, md5((string) $plainPassword . $salt))) {
                return true;
            }
        }

        return hash_equals($storedPassword, (string) $plainPassword);
    }

    protected static function looksLikeHashValue(string $value): bool
    {
        if (preg_match('/^\$2y\$|^\$2a\$|^\$argon2/i', $value)) {
            return true;
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $value)) {
            return true;
        }

        if (strpos($value, ':') !== false) {
            [$hash, $salt] = explode(':', $value, 2);
            return $hash !== '' && $salt !== '';
        }

        return false;
    }
}
