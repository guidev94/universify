<?php namespace Siamfesp\Newsletter\Models;

use Model;
use October\Rain\Database\Traits\Validation;

class Subscriber extends Model
{
    use Validation;

    public $table = 'siamfesp_newsletter_subscribers';
    protected $guarded = [];

    public $rules = [
        'email' => 'required|email',
    ];

    public $customMessages = [
        'email.required' => 'Informe o e-mail.',
        'email.email' => 'Informe um e-mail válido.',
        'email.unique' => 'Este e-mail já está cadastrado na newsletter.',
    ];

    public function beforeValidate()
    {
        $this->normalizeFields();

        $uniqueRule = 'unique:siamfesp_newsletter_subscribers';
        if ($this->exists && $this->id) {
            $uniqueRule .= ',email,' . $this->id;
        }

        $this->rules['email'] = 'required|email|' . $uniqueRule;
    }

    public function beforeSave()
    {
        $this->normalizeFields();
        $this->is_active = (bool) $this->is_active;
    }

    protected function normalizeFields(): void
    {
        $this->name = trim((string) $this->name) ?: null;
        $email = trim((string) $this->email);
        $this->email = function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
        $this->source_page = trim((string) $this->source_page) ?: null;
        $this->ip_address = trim((string) $this->ip_address) ?: null;
        $this->user_agent = trim((string) $this->user_agent) ?: null;
    }
}
