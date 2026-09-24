<?php namespace Siamfesp\Lgpd\Models;

use Model;
use October\Rain\Database\Traits\Validation;

class Consent extends Model
{
    use Validation;

    public $table = 'siamfesp_lgpd_cookie_consents';
    protected $guarded = [];

    public $rules = [
        'consent_token' => 'required',
        'categories' => 'required',
    ];

    public function beforeValidate()
    {
        $this->normalizeFields();
    }

    public function beforeSave()
    {
        $this->normalizeFields();
    }

    protected function normalizeFields(): void
    {
        $this->consent_token = trim((string) $this->consent_token);
        $this->consent_type = trim((string) $this->consent_type) ?: 'custom';
        $this->categories = trim((string) $this->categories) ?: 'necessary';
        $this->source_page = trim((string) $this->source_page) ?: null;
        $this->ip_address = trim((string) $this->ip_address) ?: null;
        $this->user_agent = trim((string) $this->user_agent) ?: null;
    }
}
